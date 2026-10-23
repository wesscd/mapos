<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Equipamento_service
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->library('os_workflow');
    }

    private function transaction(callable $work)
    {
        $db = $this->ci->db;
        $db->trans_begin();
        try {
            $result = $work();
            if (! $db->trans_status()) {
                throw new RuntimeException('Não foi possível salvar o equipamento.');
            }
            $db->trans_commit();
            return $result;
        } catch (Throwable $error) {
            $db->trans_rollback();
            throw $error;
        }
    }

    private function text($value, $limit, $required = false)
    {
        if (! is_scalar($value) && $value !== null) {
            throw new DomainException('Informe um texto válido.');
        }
        $value = trim((string) $value);
        if (($required && $value === '') || mb_strlen($value) > $limit) {
            throw new DomainException('Preencha os campos obrigatórios e respeite os limites indicados.');
        }
        return $value;
    }

    public function get($id)
    {
        return $this->ci->db->select('equipamentos.*, clientes.nomeCliente, marcas.marca')->from('equipamentos')
            ->join('clientes', 'clientes.idClientes = equipamentos.clientes_id')
            ->join('marcas', 'marcas.idMarcas = equipamentos.marcas_id', 'left')->where('idEquipamentos', (int) $id)->get()->row();
    }

    public function save(array $input, $id = null)
    {
        return $this->transaction(function () use ($input, $id) {
            $customer = filter_var($input['clientes_id'] ?? null, FILTER_VALIDATE_INT);
            if (! $customer || ! $this->ci->db->get_where('clientes', ['idClientes' => $customer])->row()) {
                throw new DomainException('Selecione um cliente válido.');
            }
            if ($id) {
                $old = $this->ci->db->query('SELECT * FROM equipamentos WHERE idEquipamentos = ? FOR UPDATE', [(int) $id])->row();
                if (! $old) {
                    throw new DomainException('Equipamento não encontrado.');
                }
                if ((int) $old->clientes_id !== $customer && $this->ci->db->query('SELECT idEquipamentos_os FROM equipamentos_os WHERE equipamentos_id = ? LIMIT 1 FOR UPDATE', [(int) $id])->row()) {
                    throw new DomainException('Equipamentos com histórico não podem mudar de cliente.');
                }
            }
            $data = ['clientes_id' => $customer, 'equipamento' => $this->text($input['equipamento'] ?? '', 150, true)];
            if (array_key_exists('checklist_tipo', $input)) {
                if (! is_string($input['checklist_tipo']) || ! in_array($input['checklist_tipo'], ['', 'notebook', 'desktop', 'outros'], true)) {
                    throw new DomainException('Selecione um tipo válido para o checklist.');
                }
                $data['checklist_tipo'] = $input['checklist_tipo'] ?: null;
            }
            foreach (['num_serie' => 80, 'modelo' => 80, 'cor' => 45, 'descricao' => 150] as $field => $limit) {
                $data[$field] = $this->text($input[$field] ?? '', $limit);
            }
            $brand = $this->text($input['marca'] ?? '', 100);
            $data['marcas_id'] = null;
            if ($brand !== '') {
                $row = $this->ci->db->get_where('marcas', ['marca' => $brand])->row();
                if (! $row) {
                    $this->ci->db->insert('marcas', ['marca' => $brand, 'cadastro' => date('Y-m-d'), 'situacao' => 1]);
                }
                $data['marcas_id'] = $row ? $row->idMarcas : $this->ci->db->insert_id();
            }
            if ($id) {
                $this->ci->db->where('idEquipamentos', $id)->update('equipamentos', $data);
            } else {
                $this->ci->db->insert('equipamentos', $data);
                $id = $this->ci->db->insert_id();
            }
            return (int) $id;
        });
    }

    public function link($orderId, $equipmentId)
    {
        return $this->transaction(function () use ($orderId, $equipmentId) {
            $order = $this->ci->db->query('SELECT * FROM os WHERE idOs = ? FOR UPDATE', [(int) $orderId])->row();
            $equipment = $this->ci->db->query('SELECT * FROM equipamentos WHERE idEquipamentos = ? FOR UPDATE', [(int) $equipmentId])->row();
            if (! $order || ! $equipment || (int) $order->clientes_id !== (int) $equipment->clientes_id) {
                throw new DomainException('O equipamento deve pertencer ao cliente desta OS.');
            }
            if ($order->entregue_em || in_array($order->status, ['Cancelado', 'Faturado'], true) || $order->faturado) {
                throw new DomainException('Vincule os equipamentos antes de encerrar o atendimento.');
            }
            $existing = $this->ci->db->get_where('equipamentos_os', ['os_id' => $orderId, 'equipamentos_id' => $equipmentId])->row();
            if ($existing) {
                return (int) $existing->idEquipamentos_os;
            }
            $this->ci->db->insert('equipamentos_os', ['os_id' => $orderId, 'equipamentos_id' => $equipmentId]);
            $id = $this->ci->db->insert_id();
            $this->ci->os_workflow->event($orderId, 'equipamento', 'Equipamento #' . (int) $equipmentId . ' vinculado. Recebimento físico ainda não confirmado.');
            if ($equipment->checklist_tipo) {
                $this->ci->load->library('checklist_service');
                $model = $this->ci->db->get_where('check_modelos', ['tipo' => $equipment->checklist_tipo, 'ativo' => 1])->row();
                if ($model) {
                    $this->ci->checklist_service->apply($orderId, $equipment->checklist_tipo, $equipmentId);
                }
            }
            return (int) $id;
        });
    }

    public function intake($orderId, $linkId, array $input)
    {
        return $this->transaction(function () use ($orderId, $linkId, $input) {
            $order = $this->ci->db->query('SELECT * FROM os WHERE idOs = ? FOR UPDATE', [(int) $orderId])->row();
            $link = $this->ci->db->get_where('equipamentos_os', ['idEquipamentos_os' => (int) $linkId, 'os_id' => (int) $orderId])->row();
            if (! $order || ! $link || $order->entregue_em || $order->status === 'Cancelado') {
                throw new DomainException('Este atendimento não permite registrar o recebimento.');
            }
            if ($this->ci->db->get_where('equipamento_recibos', ['vinculo_id' => $linkId, 'tipo' => 'entrada'])->row()) {
                throw new DomainException('O recebimento deste equipamento já foi registrado.');
            }
            // Lock the equipment after its OS, in the same order used by link().
            $this->ci->db->query('SELECT idEquipamentos FROM equipamentos WHERE idEquipamentos = ? FOR UPDATE', [$link->equipamentos_id]);
            $pending = $this->ci->db->query("SELECT r.id FROM equipamento_recibos r JOIN equipamentos_os v ON v.idEquipamentos_os = r.vinculo_id LEFT JOIN equipamento_recibos s ON s.vinculo_id = r.vinculo_id AND s.tipo = 'saida' WHERE v.equipamentos_id = ? AND r.tipo = 'entrada' AND s.id IS NULL LIMIT 1 FOR UPDATE", [$link->equipamentos_id])->row();
            if ($pending) {
                throw new DomainException('Este equipamento já possui recebimento sem entrega registrada. Confira o atendimento anterior.');
            }
            $equipment = $this->get($link->equipamentos_id);
            if (! $equipment || (int) $equipment->clientes_id !== (int) $order->clientes_id) {
                throw new DomainException('Confira o cliente do equipamento antes de receber.');
            }
            $snapshot = ['os' => (int) $orderId, 'equipamento_id' => (int) $equipment->idEquipamentos,
                'empresa' => $this->ci->db->select('nome, cnpj, telefone')->limit(1)->get('emitente')->row_array(),
                'cliente' => $equipment->nomeCliente, 'equipamento' => $equipment->equipamento, 'marca' => $equipment->marca,
                'modelo' => $equipment->modelo, 'serie' => $equipment->num_serie, 'cor' => $equipment->cor,
                'acessorios' => $this->text($input['acessorios'] ?? '', 2000, true),
                'condicoes' => $this->text($input['condicoes'] ?? '', 2000, true),
                'entregue_por' => $this->text($input['entregue_por'] ?? '', 150, true),
                'fotos' => array_map('intval', array_column($this->photos($equipment->idEquipamentos), 'id'))];
            $this->ci->load->library('vistoria_service');
            $snapshot['vistoria'] = $this->ci->vistoria_service->get($linkId);
            $id = $this->receipt($linkId, 'entrada', $snapshot);
            $this->ci->os_workflow->event($orderId, 'recebimento', 'Recebimento físico do equipamento #' . (int) $equipment->idEquipamentos . ' registrado. Comprovante #' . $id . '.');
            return $id;
        });
    }

    private function receipt($linkId, $type, array $snapshot)
    {
        $this->ci->db->insert('equipamento_recibos', ['vinculo_id' => $linkId, 'tipo' => $type,
            'dados' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'created_at' => date('Y-m-d H:i:s'),
            'usuario_id' => $this->ci->session->userdata('id_admin') ?: null]);
        return (int) $this->ci->db->insert_id();
    }

    // Called only inside Os_workflow::deliver(), with the OS row locked.
    public function deliveryReceipts($orderId, $recipient, $accepted = false)
    {
        $entries = $this->ci->db->select('equipamento_recibos.*')->from('equipamento_recibos')
            ->join('equipamentos_os', 'equipamentos_os.idEquipamentos_os = equipamento_recibos.vinculo_id')
            ->where('os_id', (int) $orderId)->where('tipo', 'entrada')->get()->result();
        $links = $this->ci->db->where('os_id', (int) $orderId)->count_all_results('equipamentos_os');
        if ($links !== count($entries)) {
            throw new DomainException('Registre o recebimento de todos os equipamentos vinculados antes de confirmar a entrega.');
        }
        if ($entries) {
            $recipient = $this->text($recipient, 150, true);
            if ($accepted !== true) {
                throw new DomainException('Confirme a conferência e o aceite de quem retira antes de registrar a entrega.');
            }
        }
        $this->ci->load->library('vistoria_service');
        $actor = $this->ci->session->userdata('id_admin') ?: null;
        $person = $actor ? $this->ci->db->select('nome')->get_where('usuarios', ['idUsuarios' => $actor])->row() : null;
        foreach ($entries as $entry) {
            $exit = $this->ci->vistoria_service->get($entry->vinculo_id, 'saida');
            if (! $exit) {
                throw new DomainException('Registre a vistoria de saída de todos os equipamentos antes de confirmar a entrega.');
            }
            $snapshot = json_decode($entry->dados, true, 512, JSON_THROW_ON_ERROR);
            $snapshot['retirado_por'] = $recipient;
            $snapshot['comprovante_entrada'] = (int) $entry->id;
            $snapshot['vistoria_saida'] = $exit;
            $snapshot['aceite'] = ['confirmado' => true, 'registrado_em' => date('Y-m-d H:i:s'),
                'registrado_por' => $person ? $person->nome : 'Registro do sistema', 'usuario_id' => $actor,
                'declaracao' => 'A equipe registrou que a pessoa identificada conferiu o equipamento, os acessórios e as ressalvas e confirmou a retirada.'];
            $this->receipt($entry->vinculo_id, 'saida', $snapshot);
        }
    }

    public function photos($id)
    {
        return $this->ci->db->select('id, created_at')->order_by('id')->get_where('equipamento_fotos', ['equipamento_id' => (int) $id])->result_array();
    }

    public function encodePhoto($bytes, $maxBytes = 2097152, $maxPixels = 12000000)
    {
        if (! is_string($bytes) || strlen($bytes) > $maxBytes) {
            throw new DomainException('Envie uma foto JPEG ou PNG dentro do limite indicado.');
        }
        $info = @getimagesizefromstring($bytes);
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true) || $info[0] * $info[1] > $maxPixels) {
            throw new DomainException('Use JPEG ou PNG dentro do limite de resolução indicado.');
        }
        $image = @imagecreatefromstring($bytes);
        if (! $image) {
            throw new DomainException('Não foi possível ler a foto.');
        }
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $stream = tmpfile();
            if ($stream) {
                fwrite($stream, $bytes);
                rewind($stream);
                $exif = @exif_read_data($stream);
                fclose($stream);
                $orientation = (int) ($exif['Orientation'] ?? 1);
                $rotation = [3 => 180, 4 => 180, 5 => 90, 6 => -90, 7 => -90, 8 => 90][$orientation] ?? 0;
                if ($rotation) {
                    $rotated = imagerotate($image, $rotation, 0);
                    imagedestroy($image);
                    $image = $rotated;
                }
                if (in_array($orientation, [2, 4, 5, 7], true)) {
                    imageflip($image, IMG_FLIP_HORIZONTAL);
                }
            }
        }
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, 1600 / max($width, $height));
        $resized = imagescale($image, max(1, (int) ($width * $scale)), max(1, (int) ($height * $scale)));
        ob_start();
        imagejpeg($resized, null, 82);
        $jpeg = ob_get_clean();
        imagedestroy($image);
        imagedestroy($resized);
        return $jpeg;
    }

    public function photo($id, $bytes)
    {
        $jpeg = $this->encodePhoto($bytes);
        return $this->transaction(function () use ($id, $jpeg) {
            $row = $this->ci->db->query('SELECT idEquipamentos FROM equipamentos WHERE idEquipamentos = ? FOR UPDATE', [(int) $id])->row();
            if (! $row || count($this->photos($id)) >= 12) {
                throw new DomainException('Equipamento inexistente ou limite de 12 fotos atingido.');
            }
            $this->ci->db->insert('equipamento_fotos', ['equipamento_id' => (int) $id, 'imagem' => $jpeg,
                'created_at' => date('Y-m-d H:i:s'), 'usuario_id' => $this->ci->session->userdata('id_admin') ?: null]);
            return (int) $this->ci->db->insert_id();
        });
    }
}
