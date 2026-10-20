<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Vistoria_service
{
    public const ANGLES = ['frente' => 'Frente', 'verso' => 'Verso', 'aberto' => 'Aberto', 'fechado' => 'Fechado', 'teclado' => 'Teclado'];
    public const STATES = ['bom' => 'Bom estado aparente', 'marcas' => 'Marcas de uso', 'danos' => 'Danos aparentes', 'nao_avaliado' => 'Não avaliado'];
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->library('equipamento_service');
    }

    private function text($value, $limit, $required = true)
    {
        if (! is_scalar($value) && $value !== null) {
            throw new DomainException('Informe um texto válido para a vistoria.');
        }
        $value = trim((string) $value);
        if (($required && $value === '') || mb_strlen($value) > $limit) {
            throw new DomainException('Preencha estado, condições e acessórios da vistoria, respeitando os limites indicados.');
        }
        return $value;
    }

    public function uploads()
    {
        $photos = [];
        foreach (self::ANGLES as $angle => $label) {
            $file = $_FILES['vistoria_foto_' . $angle] ?? null;
            if (! $file || ($file['error'] ?? null) === UPLOAD_ERR_NO_FILE) {
                if ($this->text($this->ci->input->post('vistoria_obs_' . $angle), 500, false) !== '') {
                    throw new DomainException('A observação de ' . $label . ' precisa acompanhar uma foto. Use o campo de condições para observações gerais.');
                }
                continue;
            }
            if (($file['error'] ?? null) !== UPLOAD_ERR_OK || ! is_string($file['tmp_name'] ?? null)
                || ! is_uploaded_file($file['tmp_name']) || ($file['size'] ?? 0) > 8388608) {
                throw new DomainException('Falha na foto de ' . $label . '. Use JPEG/PNG de até 8 MB. Selecione as fotos novamente antes de salvar.');
            }
            $photos[] = ['perspectiva' => $angle, 'bytes' => file_get_contents($file['tmp_name']),
                'observacao' => $this->ci->input->post('vistoria_obs_' . $angle)];
        }
        return $photos;
    }

    public function requested(array $input, array $photos)
    {
        if ($photos) {
            return true;
        }
        foreach (['vistoria_estado', 'vistoria_condicoes', 'vistoria_acessorios', 'vistoria_receber', 'vistoria_entregue_por'] as $field) {
            if (isset($input[$field]) && $input[$field] !== '') {
                return true;
            }
        }
        return false;
    }

    private function transaction(callable $work)
    {
        $db = $this->ci->db;
        $db->trans_begin();
        try {
            $result = $work();
            if (! $db->trans_status()) {
                throw new RuntimeException('Não foi possível registrar a vistoria.');
            }
            $db->trans_commit();
            return $result;
        } catch (Throwable $error) {
            $db->trans_rollback();
            throw $error;
        }
    }

    private function lock($orderId, $linkId, $type)
    {
        $order = $this->ci->db->query('SELECT * FROM os WHERE idOs = ? FOR UPDATE', [(int) $orderId])->row();
        $link = $this->ci->db->get_where('equipamentos_os', ['idEquipamentos_os' => (int) $linkId, 'os_id' => (int) $orderId])->row();
        if (! $order || ! $link || $order->entregue_em || ($type === 'entrada' && $order->status === 'Cancelado')) {
            throw new DomainException('Este atendimento não permite registrar vistoria.');
        }
        $entry = $this->ci->db->get_where('equipamento_recibos', ['vinculo_id' => $linkId, 'tipo' => 'entrada'])->row();
        if ($type === 'saida') {
            if (! $entry) {
                throw new DomainException('Registre o recebimento físico antes de conferir a saída.');
            }
        } elseif ($entry) {
            throw new DomainException('A vistoria de entrada foi encerrada pelo recebimento. Preserve o registro original.');
        }
        return $link;
    }

    public function get($linkId, $type = 'entrada')
    {
        $row = $this->ci->db->get_where('os_vistorias', ['vinculo_id' => (int) $linkId, 'tipo' => $type])->row_array();
        if ($row) {
            $row['fotos'] = $this->ci->db->select('id, perspectiva, observacao, created_at')->order_by('id')->get_where('os_vistoria_fotos', ['vistoria_id' => $row['id']])->result_array();
        }
        return $row;
    }

    public function register($orderId, $linkId, array $input, array $photos = [], $type = 'entrada')
    {
        $this->validateType($type);
        return $this->transaction(function () use ($orderId, $linkId, $input, $photos, $type) {
            $this->lock($orderId, $linkId, $type);
            if ($this->get($linkId, $type)) {
                throw new DomainException('A vistoria já está registrada. Acrescente fotos antes de confirmar a movimentação física.');
            }
            $state = $this->text($input['vistoria_estado'] ?? '', 30);
            if (! isset(self::STATES[$state])) {
                throw new DomainException('Selecione um estado válido para o equipamento.');
            }
            $actor = $this->ci->session->userdata('id_admin') ?: null;
            $person = $actor ? $this->ci->db->select('nome')->get_where('usuarios', ['idUsuarios' => $actor])->row() : null;
            $conference = null;
            $notes = null;
            if ($type === 'saida') {
                $conference = $this->text($input['vistoria_conferencia'] ?? '', 20);
                if (! in_array($conference, ['conferidos', 'divergencia'], true)) {
                    throw new DomainException('Informe o resultado da conferência dos acessórios.');
                }
                $notes = $this->text($input['vistoria_ressalvas'] ?? '', 2000, $conference === 'divergencia');
            }
            $this->ci->db->insert('os_vistorias', ['vinculo_id' => (int) $linkId, 'tipo' => $type, 'estado' => $state,
                'conferencia' => $conference, 'ressalvas' => $notes,
                'condicoes' => $this->text($input['vistoria_condicoes'] ?? '', 2000),
                'acessorios' => $this->text($input['vistoria_acessorios'] ?? '', 2000),
                'responsavel' => $person ? $person->nome : 'Registro do sistema', 'usuario_id' => $actor, 'created_at' => date('Y-m-d H:i:s')]);
            $id = (int) $this->ci->db->insert_id();
            $this->savePhotos($id, $photos);
            $this->ci->os_workflow->event($orderId, 'vistoria', 'Vistoria de ' . ($type === 'saida' ? 'saída' : 'entrada') . ' #' . $id . ' registrada. Estado: ' . self::STATES[$state]);
            return $id;
        });
    }

    private function savePhotos($id, array $photos)
    {
        if (count($photos) > 15) {
            throw new DomainException('Registre no máximo 15 fotos por vistoria.');
        }
        $counts = array_count_values(array_column($this->ci->db->select('perspectiva')->get_where('os_vistoria_fotos', ['vistoria_id' => $id])->result_array(), 'perspectiva'));
        foreach ($photos as $photo) {
            $angle = $this->text($photo['perspectiva'] ?? '', 20);
            if (! isset(self::ANGLES[$angle]) || ($counts[$angle] ?? 0) >= 3) {
                throw new DomainException('Use os ângulos indicados, com até três fotos por ângulo.');
            }
            $jpeg = $this->ci->equipamento_service->encodePhoto($photo['bytes'] ?? '', 8388608, 24000000);
            $this->ci->db->insert('os_vistoria_fotos', ['vistoria_id' => $id, 'perspectiva' => $angle,
                'observacao' => $this->text($photo['observacao'] ?? '', 500, false), 'imagem' => $jpeg,
                'created_at' => date('Y-m-d H:i:s'), 'usuario_id' => $this->ci->session->userdata('id_admin') ?: null]);
            $counts[$angle] = ($counts[$angle] ?? 0) + 1;
        }
    }

    private function validateType($type)
    {
        if (! in_array($type, ['entrada', 'saida'], true)) {
            throw new DomainException('Tipo de vistoria inválido.');
        }
    }

    public function append($orderId, $linkId, array $photos, $type = 'entrada')
    {
        $this->validateType($type);
        return $this->transaction(function () use ($orderId, $linkId, $photos, $type) {
            $this->lock($orderId, $linkId, $type);
            $inspection = $this->get($linkId, $type);
            if (! $inspection || ! $photos) {
                throw new DomainException('Selecione pelo menos uma foto para uma vistoria existente.');
            }
            $this->savePhotos((int) $inspection['id'], $photos);
            $this->ci->os_workflow->event($orderId, 'vistoria', 'Fotos acrescentadas à vistoria #' . (int) $inspection['id']);
        });
    }
}
