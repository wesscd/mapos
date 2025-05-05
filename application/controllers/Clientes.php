<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Clientes extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('clientes_model');
        $this->data['menuClientes'] = 'clientes';
    }

    public function index()
    {
        $this->gerenciar();
    }

    public function gerenciar()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vCliente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar clientes.');
            redirect(base_url());
        }

        $pesquisa = $this->input->get('pesquisa');

        $this->load->library('pagination');

        $this->data['configuration']['base_url'] = site_url('clientes/gerenciar/');
        $this->data['configuration']['total_rows'] = $this->clientes_model->count('clientes');
        if($pesquisa) {
            $this->data['configuration']['suffix'] = "?pesquisa={$pesquisa}";
            $this->data['configuration']['first_url'] = base_url("index.php/clientes")."\?pesquisa={$pesquisa}";
        }

        $this->pagination->initialize($this->data['configuration']);

        $this->data['results'] = $this->clientes_model->get('clientes', '*', $pesquisa, $this->data['configuration']['per_page'], $this->uri->segment(3));

        $this->data['view'] = 'clientes/clientes';

        return $this->layout();
    }

    public function adicionar()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'aCliente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para adicionar clientes.');
            redirect(base_url());
        }

        $this->load->library('form_validation');
        $this->data['custom_error'] = '';

        $senhaCliente = $this->input->post('senha') ? $this->input->post('senha') : preg_replace('/[^\p{L}\p{N}\s]/', '', set_value('documento'));

        $cpf_cnpj = preg_replace('/[^\p{L}\p{N}\s]/', '', set_value('documento'));

        if (strlen($cpf_cnpj) == 11) {
            $pessoa_fisica = true;
        } else {
            $pessoa_fisica = false;
        }

        if ($this->form_validation->run('clientes') == false) {
            $this->data['custom_error'] = (validation_errors() ? '<div class="form_error">' . validation_errors() . '</div>' : false);
        } else {
            $data = [
                'nomeCliente' => set_value('nomeCliente'),
                'contato' => set_value('contato'),
                'pessoa_fisica' => $pessoa_fisica,
                'documento' => set_value('documento'),
                'telefone' => set_value('telefone'),
                'celular' => set_value('celular'),
                'email' => set_value('email'),
                'senha' => password_hash($senhaCliente, PASSWORD_DEFAULT),
                'rua' => set_value('rua'),
                'numero' => set_value('numero'),
                'complemento' => set_value('complemento'),
                'bairro' => set_value('bairro'),
                'cidade' => set_value('cidade'),
                'estado' => set_value('estado'),
                'cep' => set_value('cep'),
                'dataCadastro' => date('Y-m-d'),
                'fornecedor' => $this->input->post('fornecedor') ? 1 : 0,
            ];

            $clienteId = $this->clientes_model->add('clientes', $data);
            if ($clienteId) {
                $this->session->set_flashdata('success', 'Cliente adicionado com sucesso!');
                log_info('Adicionou um cliente. ID: ' . $clienteId);

                // Logar configurações disponíveis
                log_message('debug', 'Configurações disponíveis em adicionar(): ' . print_r($this->data['configuration'], true));

                // Enviar mensagem de boas-vindas via WhatsApp
                $telefone = !empty($data['celular']) ? $data['celular'] : $data['telefone'];
                if ($telefone && isset($this->data['configuration']['whatsapp_enabled']) && $this->data['configuration']['whatsapp_enabled'] == '1') {
                    $telefoneEmitente = ($this->data['configuration']['use_whatsapp_empresa'] ?? 0) == '1'
                        ? ($this->data['configuration']['whatsapp_number'] ?? 'Não informado')
                        : ($this->data['configuration']['telefone_empresa'] ?? 'Não informado');
                    $placeholders = [
                        '{CLIENTE_NOME}' => $data['nomeCliente'],
                        '{EMITENTE}' => $this->data['configuration']['app_name'] ?? 'Empresa',
                        '{TELEFONE_EMITENTE}' => $telefoneEmitente,
                    ];
                    log_message('debug', "Placeholder {EMITENTE} definido como: " . ($this->data['configuration']['app_name'] ?? 'Empresa'));
                    log_message('debug', "Placeholder {TELEFONE_EMITENTE} definido como: " . $telefoneEmitente . " (use_whatsapp_empresa: " . ($this->data['configuration']['use_whatsapp_empresa'] ?? 0) . ")");
                    $this->enviarWhatsApp($telefone, 'whatsapp_cad_msg', $placeholders, $clienteId, 'boas-vindas');
                }

                redirect(site_url('clientes/'));
            } else {
                $this->data['custom_error'] = '<div class="form_error"><p>Ocorreu um erro.</p></div>';
            }
        }

        $this->data['view'] = 'clientes/adicionarCliente';

        return $this->layout();
    }

    public function editar()
    {
        if (! $this->uri->segment(3) || ! is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata('error', 'Item não pode ser encontrado, parâmetro não foi passado corretamente.');
            redirect('mapos');
        }

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'eCliente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para editar clientes.');
            redirect(base_url());
        }

        $this->load->library('form_validation');
        $this->data['custom_error'] = '';

        if ($this->form_validation->run('clientes') == false) {
            $this->data['custom_error'] = (validation_errors() ? '<div class="form_error">' . validation_errors() . '</div>' : false);
        } else {
            $senha = $this->input->post('senha');
            if ($senha != null) {
                $senha = password_hash($senha, PASSWORD_DEFAULT);

                $data = [
                    'nomeCliente' => $this->input->post('nomeCliente'),
                    'contato' => $this->input->post('contato'),
                    'documento' => $this->input->post('documento'),
                    'telefone' => $this->input->post('telefone'),
                    'celular' => $this->input->post('celular'),
                    'email' => $this->input->post('email'),
                    'senha' => $senha,
                    'rua' => $this->input->post('rua'),
                    'numero' => $this->input->post('numero'),
                    'complemento' => $this->input->post('complemento'),
                    'bairro' => $this->input->post('bairro'),
                    'cidade' => $this->input->post('cidade'),
                    'estado' => $this->input->post('estado'),
                    'cep' => $this->input->post('cep'),
                    'fornecedor' => (set_value('fornecedor') == true ? 1 : 0),
                ];
            } else {
                $data = [
                    'nomeCliente' => $this->input->post('nomeCliente'),
                    'contato' => $this->input->post('contato'),
                    'documento' => $this->input->post('documento'),
                    'telefone' => $this->input->post('telefone'),
                    'celular' => $this->input->post('celular'),
                    'email' => $this->input->post('email'),
                    'rua' => $this->input->post('rua'),
                    'numero' => $this->input->post('numero'),
                    'complemento' => $this->input->post('complemento'),
                    'bairro' => $this->input->post('bairro'),
                    'cidade' => $this->input->post('cidade'),
                    'estado' => $this->input->post('estado'),
                    'cep' => $this->input->post('cep'),
                    'fornecedor' => (set_value('fornecedor') == true ? 1 : 0),
                ];
            }

            if ($this->clientes_model->edit('clientes', $data, 'idClientes', $this->input->post('idClientes')) == true) {
                $this->session->set_flashdata('success', 'Cliente editado com sucesso!');
                log_info('Alterou um cliente. ID' . $this->input->post('idClientes'));
                redirect(site_url('clientes/editar/') . $this->input->post('idClientes'));
            } else {
                $this->data['custom_error'] = '<div class="form_error"><p>Ocorreu um erro</p></div>';
            }
        }

        $this->data['result'] = $this->clientes_model->getById($this->uri->segment(3));
        $this->data['view'] = 'clientes/editarCliente';

        return $this->layout();
    }

    public function visualizar()
    {
        if (! $this->uri->segment(3) || ! is_numeric($this->uri->segment(3))) {
            $this->session->set_flashdata('error', 'Item não pode ser encontrado, parâmetro não foi passado corretamente.');
            redirect('mapos');
        }

        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vCliente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar clientes.');
            redirect(base_url());
        }

        $this->data['custom_error'] = '';
        $this->data['result'] = $this->clientes_model->getById($this->uri->segment(3));
        $this->data['results'] = $this->clientes_model->getOsByCliente($this->uri->segment(3));
        $this->data['result_vendas'] = $this->clientes_model->getAllVendasByClient($this->uri->segment(3));
        $this->data['view'] = 'clientes/visualizar';

        return $this->layout();
    }

    public function excluir()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'dCliente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para excluir clientes.');
            redirect(base_url());
        }

        $id = $this->input->post('id');
        if ($id == null) {
            $this->session->set_flashdata('error', 'Erro ao tentar excluir cliente.');
            redirect(site_url('clientes/gerenciar/'));
        }

        $os = $this->clientes_model->getAllOsByClient($id);
        if ($os != null) {
            $this->clientes_model->removeClientOs($os);
        }

        // excluindo Vendas vinculadas ao cliente
        $vendas = $this->clientes_model->getAllVendasByClient($id);
        if ($vendas != null) {
            $this->clientes_model->removeClientVendas($vendas);
        }

        $this->clientes_model->delete('clientes', 'idClientes', $id);
        log_info('Removeu um cliente. ID' . $id);

        $this->session->set_flashdata('success', 'Cliente excluido com sucesso!');
        redirect(site_url('clientes/gerenciar/'));
    }

    /**
     * Envia uma mensagem via WhatsApp usando a Z-API e registra no histórico
     *
     * @param string $telefone Número de telefone do destinatário (com DDD, e.g., 5516992636487)
     * @param string $configChave Chave da configuração do template (e.g., whatsapp_cad_msg)
     * @param array $placeholders Array associativo com placeholders e valores (e.g., ['{CLIENTE_NOME}' => 'João'])
     * @param int $idRegistro ID do registro associado (e.g., idClientes)
     * @param string $tipo Tipo da mensagem para log (e.g., 'boas-vindas')
     * @return bool Retorna true se o envio for bem-sucedido, false caso contrário
     */
    private function enviarWhatsApp($telefone, $configChave, $placeholders, $idRegistro, $tipo)
    {
        // Verificar se o WhatsApp está habilitado
        if (!isset($this->data['configuration']['whatsapp_enabled']) || $this->data['configuration']['whatsapp_enabled'] != '1') {
            log_message('info', "Envio de WhatsApp ($tipo) para cliente ID #{$idRegistro} não realizado: WhatsApp desabilitado.");
            return false;
        }

        // Usar template do banco com placeholders
        $mensagem = $this->data['configuration'][$configChave] ?? '';
        if (empty($mensagem)) {
            log_message('error', "Envio de WhatsApp ($tipo) para cliente ID #{$idRegistro} não realizado: Template '$configChave' vazio ou não encontrado.");
            return false;
        }
        foreach ($placeholders as $key => $value) {
            $mensagem = str_replace($key, $value, $mensagem);
        }

        // Logar configuração do banco
        $templateBanco = $this->data['configuration'][$configChave] ?? 'N/A';
        log_message('debug', "Template do banco para {$configChave} (cliente ID #{$idRegistro}, tipo: {$tipo}): " . var_export($templateBanco, true));

        // Limpar o número de telefone
        $telefoneOriginal = $telefone;
        $telefone = preg_replace('/[^0-9]/', '', $telefone);
        log_message('debug', "Número de telefone original (cliente ID #{$idRegistro}, tipo: {$tipo}): '{$telefoneOriginal}', após limpeza: '{$telefone}'");

        // Adicionar código do país (55) se ausente
        if (!preg_match('/^55/', $telefone)) {
            if (strlen($telefone) == 10 || strlen($telefone) == 11) {
                $telefone = '55' . $telefone;
                log_message('debug', "Código do país '55' adicionado ao número (cliente ID #{$idRegistro}, tipo: {$tipo}): '{$telefone}'");
            }
        }

        // Validar o número
        if (strlen($telefone) < 12 || !preg_match('/^55[0-9]{10,11}$/', $telefone)) {
            log_message('error', "Envio de WhatsApp ($tipo) para cliente ID #{$idRegistro} não realizado: Número de telefone inválido ({$telefone}).");
            return false;
        }

        // Logar mensagem
        log_message('info', "Mensagem a ser enviada (cliente ID #{$idRegistro}, tipo: {$tipo}): '{$mensagem}'");

        // Configurações Z-API
        $apiUrl = $this->data['configuration']['whatsapp_api_url'] ?? '';
        $apiToken = $this->data['configuration']['whatsapp_api_token'] ?? '';
        $numeroApi = $this->data['configuration']['whatsapp_number'] ?? '';

        if (empty($apiUrl) || empty($apiToken) || empty($numeroApi)) {
            log_message('error', "Envio de WhatsApp ($tipo) para cliente ID #{$idRegistro} não realizado: Configurações da API WhatsApp incompletas.");
            return false;
        }

        // Payload conforme especificação da Z-API
        $payload = [
            'number' => $telefone,
            'body' => $mensagem,
            'saveOnTicket' => true,
            'linkPreview' => true
        ];
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
        log_message('debug', "Payload montado (cliente ID #{$idRegistro}, tipo: {$tipo}): " . $payloadJson);

        $headers = [
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: application/json; charset=utf-8'
        ];
        log_message('debug', "Headers da requisição (cliente ID #{$idRegistro}, tipo: {$tipo}): " . implode(', ', $headers));

        // Enviar via cURL
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        log_message('info', "Resposta da API WhatsApp para cliente ID #{$idRegistro} ({$tipo}): HTTP $httpCode, Resposta: " . $response);
        if ($curlError) {
            log_message('error', "Erro cURL para cliente ID #{$idRegistro} ({$tipo}): " . $curlError);
        }

        // Registrar no histórico
        $historicoData = [
            'idClientes' => $idRegistro,
            'descricao' => "Envio de WhatsApp ($tipo): '$mensagem' para $telefone (HTTP $httpCode)",
            'data' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('clientes_historico', $historicoData);
        if ($this->db->affected_rows() > 0) {
            log_message('info', "Registro adicionado em clientes_historico para cliente ID #{$idRegistro} ({$tipo}).");
        } else {
            log_message('error', "Falha ao registrar em clientes_historico para cliente ID #{$idRegistro} ({$tipo}).");
        }

        if ($httpCode == 200) {
            log_message('info', "Mensagem WhatsApp ($tipo) enviada com sucesso para cliente ID #{$idRegistro}.");
            return true;
        } else {
            log_message('error', "Falha ao enviar WhatsApp ($tipo) para cliente ID #{$idRegistro}. Código HTTP: {$httpCode}. Resposta: {$response}");
            return false;
        }
    }
}