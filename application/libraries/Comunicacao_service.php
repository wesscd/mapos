<?php

defined('BASEPATH') or exit('No direct script access allowed');

/** Transactional outbox. Only the CLI worker performs external sends. */
class Comunicacao_service
{
    private $ci;
    public const EVENTS = [
        'abertura' => 'Abertura da OS', 'orcamento' => 'Orçamento disponível',
        'status' => 'Mudança de etapa', 'prazo' => 'Alteração de prazo',
        'pronto' => 'Serviço finalizado / retirada', 'entrega' => 'Entrega registrada',
        'lembrete_orcamento' => 'Lembrete de orçamento', 'lembrete_retirada' => 'Lembrete de retirada',
    ];

    public function __construct()
    {
        $this->ci = &get_instance();
    }

    public function config()
    {
        return json_decode($this->ci->db->get_where('com_config', ['id' => 1])->row()->config, true);
    }

    public function saveConfig(array $input)
    {
        $config = [];
        foreach (['inicio' => [0, 23], 'fim' => [1, 24], 'intervalo_dias' => [1, 30], 'limite_lembretes' => [0, 5]] as $key => $range) {
            if (! isset($input[$key]) || filter_var($input[$key], FILTER_VALIDATE_INT) === false || $input[$key] < $range[0] || $input[$key] > $range[1]) {
                throw new DomainException('Informe horários, intervalo e limite válidos.');
            }
            $config[$key] = (int) $input[$key];
        }
        if ($config['inicio'] >= $config['fim']) {
            throw new DomainException('O horário final deve ser maior que o inicial.');
        }
        $config['whatsapp'] = ! empty($input['whatsapp']);
        $config['email'] = ! empty($input['email']);
        $this->ci->db->where('id', 1)->update('com_config', ['config' => json_encode($config)]);
    }

    public function preference($customer)
    {
        return $this->ci->db->get_where('com_preferencias', ['cliente_id' => (int) $customer])->row();
    }

    public function savePreference($customer, $channel, $evidence, $actor)
    {
        if (! in_array($channel, ['nenhum', 'whatsapp', 'email', 'ambos'], true)
            || ! $this->ci->db->get_where('clientes', ['idClientes' => (int) $customer])->row()) {
            throw new DomainException('Cliente ou canal inválido.');
        }
        $evidence = trim((string) $evidence);
        if (($channel !== 'nenhum' && $evidence === '') || mb_strlen($evidence) > 250) {
            throw new DomainException('Registre a autorização do cliente em até 250 caracteres.');
        }
        $this->ci->db->replace('com_preferencias', [
            'cliente_id' => (int) $customer, 'canal' => $channel, 'evidencia' => $evidence,
            'autor' => $actor, 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        log_info('Preferência de comunicação atualizada. Cliente: ' . (int) $customer . '; canal: ' . $channel);
    }

    public function saveTemplate($id, $text, $enabled)
    {
        $text = trim((string) $text);
        if ($text === '' || mb_strlen($text) > 3000 || ! $this->ci->db->get_where('com_modelos', ['id' => (int) $id])->row()) {
            throw new DomainException('Informe um modelo válido com até 3.000 caracteres.');
        }
        preg_match_all('/\{([^{}]+)\}/u', $text, $matches);
        if (array_diff($matches[1], ['cliente', 'os', 'equipamento', 'prazo', 'status', 'link'])) {
            throw new DomainException('O modelo contém campos desconhecidos.');
        }
        $this->ci->db->where('id', (int) $id)->update('com_modelos', ['texto' => $text, 'ativo' => (int) $enabled]);
    }

    public function destination($channel, $customer)
    {
        if ($channel === 'email') {
            return filter_var($customer->email, FILTER_VALIDATE_EMAIL) ? $customer->email : null;
        }
        $raw = trim((string) ($customer->celular ?: $customer->telefone));
        $international = str_starts_with($raw, '+') || str_starts_with($raw, '00');
        $number = preg_replace('/\D/', '', $raw);
        if (str_starts_with($number, '00')) {
            $number = substr($number, 2);
        }
        if (! $international && in_array(strlen($number), [10, 11], true)) {
            $number = '55' . $number;
        } elseif (! $international && strlen($number) < 12) {
            return null;
        }
        return preg_match('/^[1-9][0-9]{7,14}$/D', $number) ? $number : null;
    }

    public function customerStatus($status, $invoiced = false)
    {
        return [
            'Aberto' => 'Atendimento registrado',
            'Orçamento' => 'Atendimento em fase de orçamento',
            'Negociação' => 'Condições do serviço em análise',
            'Aprovado' => 'Serviço autorizado',
            'Aguardando Peças' => 'Aguardando as peças necessárias para continuar o serviço',
            'Em Andamento' => 'Serviço em execução',
            'Finalizado' => 'Serviço concluído',
            'Faturado' => $invoiced ? 'Valores do atendimento registrados' : 'Informações do atendimento atualizadas',
            'Cancelado' => 'Atendimento cancelado',
        ][$status] ?? 'Atendimento atualizado';
    }

    public function capture($id, $event, $source, array $context = [])
    {
        if (! isset(self::EVENTS[$event])) {
            return;
        }
        $db = $this->ci->db;
        $order = $db->get_where('os', ['idOs' => (int) $id])->row();
        if (! $order) {
            return;
        }
        $customer = $db->get_where('clientes', ['idClientes' => $order->clientes_id])->row();
        $preference = $this->preference($order->clientes_id);
        $config = $this->config();
        if (! $customer || ! $preference || $preference->canal === 'nenhum') {
            return;
        }
        $context += ['status' => $order->status, 'prazo' => $order->dataFinal, 'conclusao' => $order->concluida_em, 'entrega' => $order->entregue_em];
        foreach (['whatsapp', 'email'] as $channel) {
            if (! $config[$channel] || ! in_array($preference->canal, [$channel, 'ambos'], true)) {
                continue;
            }
            $template = $db->get_where('com_modelos', ['evento' => $event, 'canal' => $channel, 'ativo' => 1])->row();
            $destination = $this->destination($channel, $customer);
            if (! $template || ! $destination) {
                continue;
            }
            $text = strtr($template->texto, [
                '{cliente}' => $customer->nomeCliente, '{os}' => (string) $id,
                '{equipamento}' => mb_substr(trim(strip_tags((string) $order->descricaoProduto)), 0, 250),
                '{prazo}' => $order->dataFinal ? date('d/m/Y', strtotime($order->dataFinal)) : 'a combinar',
                '{status}' => $this->customerStatus($order->status, (bool) $order->faturado), '{link}' => site_url('mine/visualizarOs/' . (int) $id),
            ]);
            $this->enqueue($id, $customer->idClientes, $event, $channel, $destination, $text, $context, $source);
        }
    }

    private function enqueue($id, $customer, $event, $channel, $destination, $text, array $context, $source, $test = false)
    {
        $db = $this->ci->db;
        $dedup = hash('sha256', $source . ':' . $channel);
        // Unique DB constraint is authoritative; the worker also holds a process lock.
        if ($db->get_where('com_fila', ['dedup' => $dedup])->row()) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $db->insert('com_fila', [
            'chave' => bin2hex(random_bytes(24)), 'dedup' => $dedup,
            'os_id' => $id, 'cliente_id' => $customer, 'evento' => $event,
            'canal' => $channel, 'destino' => $destination, 'texto' => $text,
            'contexto' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'teste' => (int) $test, 'created_at' => $now, 'updated_at' => $now, 'agendado_em' => $now,
        ]);
    }

    public function testMessage($channel, $destination)
    {
        if (! in_array($channel, ['whatsapp', 'email'], true)) {
            throw new DomainException('Canal inválido.');
        }
        $value = $this->destination($channel, (object) ['email' => $destination, 'celular' => $destination, 'telefone' => '']);
        if (! $value) {
            throw new DomainException('Informe um e-mail válido ou telefone com DDI e DDD.');
        }
        $this->enqueue(null, null, 'teste', $channel, $value, 'Teste de comunicação do MapOS. O canal está funcionando.', [], bin2hex(random_bytes(16)), true);
    }

    public function allowed($job)
    {
        if ($job->teste) {
            return true;
        }
        $db = $this->ci->db;
        $order = $db->get_where('os', ['idOs' => $job->os_id])->row();
        $preference = $this->preference($job->cliente_id);
        $customer = $db->get_where('clientes', ['idClientes' => $job->cliente_id])->row();
        $template = $db->get_where('com_modelos', ['evento' => $job->evento, 'canal' => $job->canal, 'ativo' => 1])->row();
        if (! $order || ! $customer || $order->clientes_id != $job->cliente_id || ! $template
            || ! $preference || ! in_array($preference->canal, [$job->canal, 'ambos'], true)
            || $this->destination($job->canal, $customer) !== $job->destino) {
            return false;
        }
        $context = json_decode($job->contexto, true);
        if (isset($context['aprovacao'])) {
            return ! $order->entregue_em && ! (int) $order->faturado && $order->status !== 'Cancelado'
                && (bool) $db->get_where('os_aprovacoes', ['id' => $context['aprovacao'], 'os_id' => $job->os_id, 'status' => 'pendente'])->row();
        }
        if (in_array($job->evento, ['pronto', 'lembrete_retirada'], true)) {
            return in_array($order->status, ['Finalizado', 'Faturado'], true) && ! $order->entregue_em
                && $order->concluida_em === ($context['conclusao'] ?? null);
        }
        if ($job->evento === 'entrega') {
            return ! empty($order->entregue_em) && $order->entregue_em === ($context['entrega'] ?? null);
        }
        if ($job->evento === 'status') {
            return $order->status === ($context['status'] ?? null);
        }
        if ($job->evento === 'prazo') {
            return $order->dataFinal === ($context['prazo'] ?? null) && $order->status !== 'Cancelado';
        }
        return $order->status !== 'Cancelado';
    }

    private function state($job, $state, $description, $delay = 0)
    {
        $now = date('Y-m-d H:i:s');
        $data = ['status' => $state, 'erro' => mb_substr($description, 0, 250), 'updated_at' => $now];
        if ($delay) {
            $data['agendado_em'] = date('Y-m-d H:i:s', time() + $delay);
        }
        $this->ci->db->where('id', $job->id)->update('com_fila', $data);
        $this->ci->db->insert('com_tentativas', ['fila_id' => $job->id, 'status' => $state, 'descricao' => $data['erro'], 'created_at' => $now]);
    }

    public function manageJob($id, $action)
    {
        $db = $this->ci->db;
        $job = $db->get_where('com_fila', ['id' => (int) $id])->row();
        if (! $job) {
            throw new DomainException('Mensagem não encontrada.');
        }
        // Compare-and-set prevents racing a worker that has already started a send.
        if ($action === 'cancelar') {
            $db->where_in('status', ['pendente', 'falhou'])->where('id', $job->id)->update('com_fila', ['status' => 'cancelado', 'updated_at' => date('Y-m-d H:i:s')]);
        } elseif ($action === 'repetir' && $job->status === 'falhou') {
            $db->where('status', 'falhou')->where('id', $job->id)->update('com_fila', ['status' => 'pendente', 'tentativas' => 0, 'agendado_em' => date('Y-m-d H:i:s')]);
        } else {
            throw new DomainException('Ação indisponível. Envios incertos exigem conferência; não são reenviados automaticamente.');
        }
        if (! $db->affected_rows()) {
            throw new DomainException('O estado da mensagem mudou. Atualize a página.');
        }
        log_info('Comunicação: ' . $action . ' mensagem ' . $job->id);
    }

    public function reminders()
    {
        $config = $this->config();
        if (! $config['limite_lembretes'] || (! $config['whatsapp'] && ! $config['email'])) {
            return;
        }
        $db = $this->ci->db;
        $sources = $db->where('status', 'pendente')->get('os_aprovacoes')->result();
        foreach ($sources as $approval) {
            $this->remind($approval->os_id, 'lembrete_orcamento', 'approval:' . $approval->id, $approval->created_at, ['aprovacao' => (int) $approval->id], $config);
        }
        $orders = $db->where_in('status', ['Finalizado', 'Faturado'])->where('entregue_em', null)->where('concluida_em IS NOT NULL', null, false)->get('os')->result();
        foreach ($orders as $order) {
            $this->remind($order->idOs, 'lembrete_retirada', 'pickup:' . $order->idOs . ':' . $order->concluida_em, $order->concluida_em, [], $config);
        }
    }

    private function remind($id, $event, $source, $since, array $context, array $config)
    {
        $age = (int) floor((time() - strtotime($since)) / 86400 / $config['intervalo_dias']);
        if ($age < 1) {
            return;
        }
        // Only today's slot, never a burst of overdue reminders after an outage.
        $slot = min($age, $config['limite_lembretes']);
        $existing = $this->ci->db->where('os_id', $id)->where('evento', $event)->where('created_at >', date('Y-m-d H:i:s', time() - $config['intervalo_dias'] * 86400))->count_all_results('com_fila');
        if (! $existing) {
            $this->capture($id, $event, $source . ':' . $slot, $context);
        }
    }

    public function smtpReady()
    {
        $config = $this->smtpConfig();
        return ! empty($config['smtp_host']) && ! empty($config['smtp_user']) && ! empty($config['smtp_pass'])
            && $config['smtp_pass'] !== 'senhadoemail' && filter_var($config['smtp_user'], FILTER_VALIDATE_EMAIL);
    }

    public function smtpConfig()
    {
        $file = '/var/lib/mapos-communication/smtp.json';
        if (is_readable($file)) {
            return json_decode(file_get_contents($file), true) ?: [];
        }
        return [
            'protocol' => 'smtp', 'smtp_host' => $_ENV['EMAIL_SMTP_HOST'] ?? '',
            'smtp_user' => $_ENV['EMAIL_SMTP_USER'] ?? '', 'smtp_pass' => $_ENV['EMAIL_SMTP_PASS'] ?? '',
            'smtp_port' => (int) ($_ENV['EMAIL_SMTP_PORT'] ?? 587), 'smtp_crypto' => $_ENV['EMAIL_SMTP_CRYPTO'] ?? 'tls',
        ];
    }

    public function saveGmail($email, $password)
    {
        $email = trim((string) $email);
        $password = preg_replace('/\s/', '', (string) $password);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! preg_match('/^[a-zA-Z0-9]{16}$/D', $password)) {
            throw new DomainException('Informe o e-mail e uma senha de app Google de 16 caracteres.');
        }
        $directory = '/var/lib/mapos-communication';
        if (! is_dir($directory) || ! is_writable($directory)) {
            throw new DomainException('Armazenamento privado indisponível. Verifique o serviço PHP.');
        }
        $file = tempnam($directory, '.smtp-');
        try {
            chmod($file, 0600);
            $written = file_put_contents($file, json_encode([
                'protocol' => 'smtp', 'smtp_host' => 'smtp.gmail.com', 'smtp_port' => 587,
                'smtp_crypto' => 'tls', 'smtp_user' => $email, 'smtp_pass' => $password,
            ], JSON_THROW_ON_ERROR), LOCK_EX);
            if ($written === false || ! rename($file, $directory . '/smtp.json')) {
                throw new DomainException('Não foi possível salvar o SMTP privado.');
            }
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
        }
        log_info('Configuração SMTP da comunicação atualizada.');
    }

    public function process(?callable $transport = null)
    {
        $db = $this->ci->db;
        $lock = $db->query('SELECT GET_LOCK(?, 0) AS acquired', ['mapos.communication.' . $db->database])->row();
        if (! (int) $lock->acquired) {
            return;
        }
        try {
            $this->reminders();
            // A dead worker may have sent before it stopped. Never blindly retry.
            $db->where('status', 'enviando')->where('updated_at <', date('Y-m-d H:i:s', time() - 300))->update('com_fila', ['status' => 'incerto', 'erro' => 'Processamento interrompido; aguarda conferência.']);
            $config = $this->config();
            $jobs = $db->where('status', 'pendente')->where('agendado_em <=', date('Y-m-d H:i:s'))->order_by('id')->limit(30)->get('com_fila')->result();
            foreach ($jobs as $job) {
                if (! $this->allowed($job)) {
                    $this->state($job, 'cancelado', 'Preferência, destinatário ou atendimento alterado.');
                    continue;
                }
                $hour = (int) date('G');
                if (! $job->teste && (! $config[$job->canal] || $hour < $config['inicio'] || $hour >= $config['fim'])) {
                    continue;
                }
                $db->where('id', $job->id)->where('status', 'pendente')->set('tentativas', 'tentativas + 1', false)->update('com_fila', ['status' => 'enviando', 'updated_at' => date('Y-m-d H:i:s')]);
                if (! $db->affected_rows()) {
                    continue;
                }
                $job->tentativas++;
                try {
                    $result = $transport ? $transport($job) : $this->send($job);
                } catch (Throwable $error) {
                    $result = ['state' => 'incerto', 'message' => 'Resultado do envio não confirmado; confira antes de repetir.'];
                }
                $state = $result['state'];
                if ($state === 'throttle') {
                    $db->where('id', $job->id)->set('tentativas', 'tentativas - 1', false)->update('com_fila');
                    $this->state($job, 'pendente', $result['message'], 15);
                } elseif ($state === 'retry') {
                    $this->state($job, $job->tentativas >= 5 ? 'falhou' : 'pendente', $result['message'], min(3600, 60 * (2 ** $job->tentativas)));
                } else {
                    $this->state($job, $state, $result['message']);
                }
            }
            if (! $transport) {
                $this->receipts();
            }
        } finally {
            $db->query('SELECT RELEASE_LOCK(?)', ['mapos.communication.' . $db->database]);
        }
    }

    private function send($job)
    {
        if ($job->canal === 'email') {
            if (! $this->smtpReady()) {
                return ['state' => 'retry', 'message' => 'Configure o SMTP privado antes de enviar e-mails.'];
            }
            $this->ci->load->library('email');
            $email = $this->ci->email;
            $email->clear(true);
            $email->initialize($this->smtpConfig() + ['smtp_timeout' => 20, 'newline' => "\r\n", 'crlf' => "\r\n"]);
            $email->set_mailtype('text');
            $email->from($this->smtpConfig()['smtp_user'], 'MapOS');
            $email->to($job->destino);
            $email->subject($job->os_id ? 'Atualização do atendimento #' . $job->os_id : 'Teste de comunicação MapOS');
            $email->message($job->texto);
            return $email->send(true)
                ? ['state' => 'enviado', 'message' => 'Aceito pelo servidor SMTP; entrega não confirmada.']
                : ['state' => 'incerto', 'message' => 'SMTP não confirmou o envio. Confira o provedor antes de repetir.'];
        }
        $this->ci->load->library('whatsapp_gateway');
        $response = $this->ci->whatsapp_gateway->request('POST', '/messages', ['key' => $job->chave, 'to' => $job->destino, 'text' => $job->texto]);
        if ($response['code'] === 429) {
            return ['state' => 'throttle', 'message' => 'Aguardando intervalo de envio; não consome tentativa.'];
        }
        if ($response['code'] === 503) {
            return ['state' => 'retry', 'message' => 'WhatsApp desconectado ou aguardando intervalo de envio.'];
        }
        if ($response['code'] === 422) {
            return ['state' => 'falhou', 'message' => 'Número não disponível no WhatsApp ou mensagem inválida.'];
        }
        $state = $response['data']['status'] ?? 'incerto';
        $state = ['sent' => 'enviado', 'delivered' => 'entregue', 'read' => 'lido'][$state] ?? 'incerto';
        return ['state' => $state, 'message' => $state === 'incerto' ? 'Confirmação pendente; sem reenvio automático.' : 'Aceito pelo WhatsApp.'];
    }

    private function receipts()
    {
        $this->ci->load->library('whatsapp_gateway');
        $jobs = $this->ci->db->where('canal', 'whatsapp')->where_in('status', ['enviado', 'entregue', 'incerto'])->where('created_at >', date('Y-m-d H:i:s', time() - 7 * 86400))->order_by('updated_at')->limit(50)->get('com_fila')->result();
        foreach ($jobs as $job) {
            $response = $this->ci->whatsapp_gateway->request('GET', '/messages/' . $job->chave);
            if ($response['code'] === 503 || $response['code'] === 0) {
                break;
            }
            if ($response['code'] !== 200) {
                continue;
            }
            $state = ['sent' => 'enviado', 'delivered' => 'entregue', 'read' => 'lido'][$response['data']['status'] ?? ''] ?? null;
            if ($state && $state !== $job->status) {
                $this->state($job, $state, 'Confirmação recebida do WhatsApp.');
            } else {
                $this->ci->db->where('id', $job->id)->update('com_fila', ['updated_at' => date('Y-m-d H:i:s')]);
            }
        }
    }
}
