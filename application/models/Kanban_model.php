<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Kanban_model extends CI_Model
{
    public const COLUMNS = ['abertas' => 'Abertas', 'orcamento' => 'Orçamento', 'aprovacao' => 'Aguardando aprovação', 'autorizadas' => 'Autorizadas', 'execucao' => 'Em execução', 'pecas' => 'Aguardando peças', 'retirada' => 'Aguardando retirada', 'entregues' => 'Entregues', 'canceladas' => 'Canceladas'];
    public const TARGETS = ['abertas' => 'Aberto', 'orcamento' => 'Orçamento', 'autorizadas' => 'Aprovado', 'execucao' => 'Em Andamento', 'pecas' => 'Aguardando Peças', 'retirada' => 'Finalizado'];

    public function board($query = '', $technician = 0, $closed = false)
    {
        $this->db->select('os.idOs, os.status, os.dataInicial, os.dataFinal, os.concluida_em, os.entregue_em, os.faturado, os.usuarios_id, clientes.nomeCliente, usuarios.nome, eq.equipamentos')
            ->select("EXISTS(SELECT 1 FROM os_aprovacoes a WHERE a.os_id = os.idOs AND a.status = 'pendente') AS aprovacao_pendente", false)
            ->from('os')->join('clientes', 'clientes.idClientes = os.clientes_id')->join('usuarios', 'usuarios.idUsuarios = os.usuarios_id')
            ->join("(SELECT v.os_id, GROUP_CONCAT(CONCAT(e.equipamento, ' ', COALESCE(e.modelo, ''), ' ', COALESCE(e.num_serie, '')) SEPARATOR ' · ') AS equipamentos FROM equipamentos_os v JOIN equipamentos e ON e.idEquipamentos = v.equipamentos_id GROUP BY v.os_id) eq", 'eq.os_id = os.idOs', 'left', false);
        if ($closed) {
            $since = date('Y-m-d', strtotime('-30 days'));
            $this->db->group_start()->group_start()->where('entregue_em', null)->where('status !=', 'Cancelado')->group_end()
                ->or_where('entregue_em >=', $since)->or_group_start()->where('status', 'Cancelado')->where('dataInicial >=', $since)->group_end()->group_end();
        } else {
            $this->db->where('entregue_em', null)->where('status !=', 'Cancelado');
        }
        if ($technician > 0) {
            $this->db->where('os.usuarios_id', (int) $technician);
        }
        if ($query !== '') {
            $this->db->group_start()->like('clientes.nomeCliente', $query)->or_like('eq.equipamentos', $query);
            if (ctype_digit($query)) {
                $this->db->or_where('os.idOs', (int) $query);
            }
            $this->db->group_end();
        }
        $rows = $this->db->order_by('os.dataFinal IS NULL', 'asc', false)->order_by('os.dataFinal')->order_by('os.idOs', 'desc')->limit(501)->get()->result();
        $columns = array_fill_keys(array_keys(self::COLUMNS), []);
        foreach (array_slice($rows, 0, 500) as $row) {
            if ($row->entregue_em) {
                $column = 'entregues';
            } elseif ($row->status === 'Cancelado') {
                $column = 'canceladas';
            } elseif (in_array($row->status, ['Finalizado', 'Faturado'], true) || $row->faturado) {
                $column = 'retirada';
            } elseif ($row->aprovacao_pendente && in_array($row->status, ['Aberto', 'Orçamento', 'Negociação'], true)) {
                $column = 'aprovacao';
            } else {
                $column = ['Orçamento' => 'orcamento', 'Negociação' => 'orcamento', 'Aprovado' => 'autorizadas', 'Em Andamento' => 'execucao', 'Aguardando Peças' => 'pecas'][$row->status] ?? 'abertas';
            }
            $row->atrasada = ! in_array($column, ['retirada', 'entregues', 'canceladas'], true) && $row->dataFinal && $row->dataFinal >= '1900-01-01' && $row->dataFinal < date('Y-m-d');
            $columns[$column][] = $row;
        }
        if (! $closed) {
            unset($columns['entregues'], $columns['canceladas']);
        }
        return ['columns' => $columns, 'limited' => count($rows) > 500, 'total' => min(count($rows), 500)];
    }
}
