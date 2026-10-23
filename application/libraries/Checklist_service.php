<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Checklist_service
{
    public const TYPES = ['notebook' => 'Notebook', 'desktop' => 'Desktop', 'outros' => 'Outros equipamentos'];
    public const SCOPES = ['verificacao' => 'Verificação / teste', 'complementar' => 'Cuidado complementar', 'autorizacao' => 'Exige autorização'];
    public const RESULTS = [
        'analise' => ['pendente' => 'Pendente', 'conforme' => 'Conforme', 'atencao' => 'Atenção', 'nao_aplica' => 'Não se aplica'],
        'execucao' => ['pendente' => 'Pendente', 'realizado' => 'Realizado', 'nao_realizado' => 'Não realizado', 'nao_aplica' => 'Não se aplica'],
    ];
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->library('os_workflow');
    }

    private function text($value, $max, $required = false)
    {
        if (! is_scalar($value) && $value !== null) {
            throw new DomainException('Informe um texto válido.');
        }
        $value = trim((string) $value);
        if (mb_strlen($value) > $max || ($required && $value === '')) {
            throw new DomainException('Preencha os campos e respeite o limite de caracteres.');
        }
        return $value;
    }

    private function actor()
    {
        return (int) $this->ci->session->userdata('id_admin') ?: null;
    }

    private function transaction(callable $work)
    {
        $db = $this->ci->db;
        $db->trans_begin();
        try {
            $result = $work();
            if (! $db->trans_status()) {
                throw new RuntimeException('Não foi possível salvar o checklist.');
            }
            $db->trans_commit();
            return $result;
        } catch (Throwable $error) {
            $db->trans_rollback();
            throw $error;
        }
    }

    private function lock($id)
    {
        $order = $this->ci->db->query('SELECT * FROM os WHERE idOs = ? FOR UPDATE', [(int) $id])->row();
        if (! $order || $order->entregue_em || $order->faturado || in_array($order->status, ['Cancelado', 'Faturado'], true)) {
            throw new DomainException('Checklist indisponível para alteração. OS entregue, faturada ou cancelada preserva seu histórico.');
        }
        return $order;
    }

    public function models()
    {
        return $this->ci->db->order_by('id')->get('check_modelos')->result();
    }

    private function definition(array $item)
    {
        $phase = $item['fase'] ?? null;
        $scope = $item['escopo'] ?? null;
        if (! is_string($phase) || ! is_string($scope) || ! isset(self::RESULTS[$phase]) || ! isset(self::SCOPES[$scope]) || ($phase === 'analise' && $scope !== 'verificacao')) {
            throw new DomainException('Selecione uma etapa e um tipo de procedimento válidos. Análise utiliza verificações.');
        }
        return ['fase' => $phase, 'descricao' => $this->text($item['descricao'] ?? '', 180, true), 'escopo' => $scope];
    }

    public function saveModel($id, array $input)
    {
        return $this->transaction(function () use ($id, $input) {
            $model = $this->ci->db->query('SELECT * FROM check_modelos WHERE id = ? FOR UPDATE', [(int) $id])->row();
            if (! $model || filter_var($input['versao'] ?? null, FILTER_VALIDATE_INT) !== (int) $model->versao) {
                throw new DomainException('O modelo foi alterado por outra pessoa. Atualize a página antes de editar.');
            }
            $items = [];
            foreach ((array) ($input['itens'] ?? []) as $item) {
                if (! is_array($item)) {
                    throw new DomainException('Itens do modelo inválidos.');
                }
                if (empty($item['remover'])) {
                    $items[] = $this->definition($item);
                }
            }
            foreach (['analise', 'execucao'] as $phase) {
                $lines = $this->text($input['novos_' . $phase] ?? '', 10000);
                foreach (preg_split('/\R/u', $lines) as $line) {
                    if (trim($line) !== '') {
                        $items[] = $this->definition(['fase' => $phase, 'descricao' => $line, 'escopo' => $phase === 'analise' ? 'verificacao' : ($input['novo_escopo'] ?? 'autorizacao')]);
                    }
                }
            }
            if (! $items || count($items) > 100) {
                throw new DomainException('Mantenha entre 1 e 100 itens por modelo.');
            }
            $this->ci->db->where('id', $model->id)->update('check_modelos', ['nome' => $this->text($input['nome'] ?? '', 100, true), 'ativo' => ! empty($input['ativo']) ? 1 : 0, 'itens' => json_encode($items, JSON_UNESCAPED_UNICODE), 'versao' => $model->versao + 1, 'atualizado_por' => $this->actor(), 'updated_at' => date('Y-m-d H:i:s')]);
        });
    }

    public function apply($orderId, $type, $equipmentId = 0)
    {
        if (! is_string($type) || ! isset(self::TYPES[$type])) {
            throw new DomainException('Selecione o tipo de equipamento.');
        }
        return $this->transaction(function () use ($orderId, $type, $equipmentId) {
            $order = $this->lock($orderId);
            $target = filter_var($equipmentId, FILTER_VALIDATE_INT);
            if ($target === false || $target < 0) {
                throw new DomainException('Equipamento inválido.');
            }
            $name = 'Atendimento sem equipamento vinculado';
            if ($target) {
                $equipment = $this->ci->db->select('equipamentos.*')->from('equipamentos')->join('equipamentos_os', 'equipamentos_os.equipamentos_id = equipamentos.idEquipamentos')->where('equipamentos_os.os_id', (int) $orderId)->where('equipamentos.idEquipamentos', $target)->where('equipamentos.clientes_id', $order->clientes_id)->get()->row();
                if (! $equipment) {
                    throw new DomainException('O equipamento precisa estar vinculado a esta OS e pertencer ao cliente.');
                }
                $name = $equipment->equipamento;
            }
            $existing = $this->ci->db->get_where('os_check_grupos', ['os_id' => (int) $orderId, 'alvo' => $target])->row();
            if ($existing) {
                return (int) $existing->id;
            }
            $model = $this->ci->db->get_where('check_modelos', ['tipo' => $type, 'ativo' => 1])->row();
            if (! $model) {
                throw new DomainException('Modelo desativado. Ative-o nas configurações de checklist.');
            }
            $this->ci->db->insert('os_check_grupos', ['os_id' => (int) $orderId, 'alvo' => $target, 'equipamento_nome' => $name, 'tipo' => $type, 'modelo_nome' => $model->nome, 'modelo_versao' => $model->versao, 'criado_por' => $this->actor(), 'created_at' => date('Y-m-d H:i:s')]);
            $group = $this->ci->db->insert_id();
            foreach (json_decode($model->itens, true) as $item) {
                $this->ci->db->insert('os_check_itens', $this->definition($item) + ['grupo_id' => $group]);
            }
            $this->ci->os_workflow->event($orderId, 'checklist', 'Modelo ' . $model->nome . ' v' . $model->versao . ' aplicado a ' . $name . '. Itens pendentes; nenhum serviço ou valor adicionado.');
            return (int) $group;
        });
    }

    public function details($orderId)
    {
        $groups = $this->ci->db->order_by('id')->get_where('os_check_grupos', ['os_id' => (int) $orderId])->result();
        if (! $groups) {
            return [];
        }
        $groupIds = array_map(fn ($group) => (int) $group->id, $groups);
        $items = $this->ci->db->select('os_check_itens.*, usuarios.nome AS responsavel')->from('os_check_itens')->join('usuarios', 'usuarios.idUsuarios = os_check_itens.atualizado_por', 'left')->where_in('grupo_id', $groupIds)->order_by('os_check_itens.id')->get()->result();
        $photos = $this->ci->db->select('os_check_fotos.id, os_check_fotos.item_id, os_check_fotos.created_at')->from('os_check_fotos')->join('os_check_itens', 'os_check_itens.id = os_check_fotos.item_id')->where_in('os_check_itens.grupo_id', $groupIds)->order_by('os_check_fotos.id')->get()->result();
        $photosByItem = [];
        foreach ($photos as $photo) {
            $photosByItem[$photo->item_id][] = $photo;
        }
        $itemsByGroup = [];
        foreach ($items as $item) {
            $item->fotos = $photosByItem[$item->id] ?? [];
            $itemsByGroup[$item->grupo_id][] = $item;
        }
        foreach ($groups as $group) {
            $group->itens = $itemsByGroup[$group->id] ?? [];
        }
        return $groups;
    }

    public function add($orderId, $groupId, array $input)
    {
        return $this->transaction(function () use ($orderId, $groupId, $input) {
            $this->lock($orderId);
            if (! $this->ci->db->get_where('os_check_grupos', ['id' => (int) $groupId, 'os_id' => (int) $orderId])->row()) {
                throw new DomainException('Checklist não pertence a esta OS.');
            }
            if ($this->ci->db->where('grupo_id', (int) $groupId)->count_all_results('os_check_itens') >= 120) {
                throw new DomainException('Limite de 120 itens por checklist.');
            }
            $item = $this->definition($input);
            $this->ci->db->insert('os_check_itens', $item + ['grupo_id' => (int) $groupId]);
            $this->ci->os_workflow->event($orderId, 'checklist', 'Item adicional: ' . $item['descricao']);
        });
    }

    public function upload()
    {
        $file = $_FILES['checklist_foto'] ?? null;
        if (! $file || ($file['error'] ?? null) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? null) !== UPLOAD_ERR_OK || ! is_string($file['tmp_name'] ?? null) || ! is_uploaded_file($file['tmp_name']) || ($file['size'] ?? 0) > 8388608) {
            throw new DomainException('Selecione uma foto JPEG/PNG de até 8 MB.');
        }
        return file_get_contents($file['tmp_name']);
    }

    public function update($orderId, $itemId, array $input, $photo = null)
    {
        return $this->transaction(function () use ($orderId, $itemId, $input, $photo) {
            $this->lock($orderId);
            if (! filter_var($itemId, FILTER_VALIDATE_INT)) {
                throw new DomainException('Selecione um item válido.');
            }
            $item = $this->ci->db->select('os_check_itens.*')->from('os_check_itens')->join('os_check_grupos', 'os_check_grupos.id = os_check_itens.grupo_id')->where('os_check_itens.id', (int) $itemId)->where('os_check_grupos.os_id', (int) $orderId)->get()->row();
            if (! $item || filter_var($input['versao'] ?? null, FILTER_VALIDATE_INT) !== (int) $item->versao) {
                throw new DomainException('Item não encontrado ou alterado por outra pessoa. Atualize antes de salvar.');
            }
            $result = $input['resultado'] ?? '';
            if (! is_string($result) || ! isset(self::RESULTS[$item->fase][$result])) {
                throw new DomainException('Selecione um resultado válido para esta etapa.');
            }
            $notes = $this->text($input['observacoes'] ?? '', 2000);
            $authorization = $this->text($input['autorizacao'] ?? '', 500);
            if (in_array($result, ['atencao', 'nao_realizado'], true) && $notes === '') {
                throw new DomainException('Registre a observação ou justificativa deste resultado.');
            }
            if ($item->fase === 'execucao' && $item->escopo === 'autorizacao' && $result === 'realizado' && $authorization === '') {
                throw new DomainException('Informe a referência da autorização ou do escopo aprovado antes de registrar esta intervenção como realizada.');
            }
            if ($photo !== null) {
                if ($this->ci->db->where('item_id', $item->id)->count_all_results('os_check_fotos') >= 3) {
                    throw new DomainException('Cada item aceita até três fotos, sem substituir evidências.');
                }
                $this->ci->load->library('equipamento_service');
                $jpeg = $this->ci->equipamento_service->encodePhoto($photo, 8388608, 24000000);
                $this->ci->db->insert('os_check_fotos', ['item_id' => $item->id, 'imagem' => $jpeg, 'criado_por' => $this->actor(), 'created_at' => date('Y-m-d H:i:s')]);
            }
            $this->ci->db->where('id', $item->id)->update('os_check_itens', ['resultado' => $result, 'observacoes' => $notes, 'autorizacao' => $authorization, 'atualizado_por' => $this->actor(), 'updated_at' => date('Y-m-d H:i:s'), 'versao' => $item->versao + 1]);
            $audit = ['antes' => ['resultado' => $item->resultado, 'observacoes' => $item->observacoes, 'autorizacao' => $item->autorizacao], 'depois' => ['resultado' => $result, 'observacoes' => $notes, 'autorizacao' => $authorization], 'foto_adicionada' => $photo !== null];
            $this->ci->db->insert('os_check_registros', ['item_id' => $item->id, 'usuario_id' => $this->actor(), 'dados' => json_encode($audit, JSON_UNESCAPED_UNICODE), 'created_at' => date('Y-m-d H:i:s')]);
            $this->ci->os_workflow->event($orderId, 'checklist', $item->descricao . ': ' . self::RESULTS[$item->fase][$result]);
        });
    }
}
