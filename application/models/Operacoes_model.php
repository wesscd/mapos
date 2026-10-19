<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Operacoes_model extends CI_Model
{
    public function summary($days, $finance = false)
    {
        $since = date('Y-m-d', strtotime('-' . (int) $days . ' days'));
        $active = ['Finalizado', 'Faturado', 'Cancelado'];
        $backlog = $this->db->where_not_in('status', $active)->count_all_results('os');
        $late = $this->db->select('os.idOs, os.dataFinal, os.status, clientes.nomeCliente, usuarios.nome')->from('os')
            ->join('clientes', 'clientes.idClientes = os.clientes_id')->join('usuarios', 'usuarios.idUsuarios = os.usuarios_id')
            ->where_not_in('os.status', $active)->where('dataFinal <', date('Y-m-d'))->order_by('dataFinal')->get()->result();
        $completed = $this->db->select('COUNT(*) total, AVG(DATEDIFF(concluida_em, dataInicial)) dias', false)
            ->where('concluida_em >=', $since)->where_in('status', ['Finalizado', 'Faturado'])->get('os')->row();
        $technicians = $this->db->select('usuarios.nome, COUNT(*) total', false)->from('os')->join('usuarios', 'usuarios.idUsuarios = os.usuarios_id')
            ->where('concluida_em >=', $since)->where_in('os.status', ['Finalizado', 'Faturado'])->group_by('usuarios.idUsuarios, usuarios.nome')->order_by('total', 'desc')->get()->result();
        $margin = [];
        if ($finance) {
            $orders = $this->db->select('os.idOs, os.valorTotal, os.valor_desconto, os.tipo_desconto, SUM(produtos_os.quantidade * produtos_os.custo_unitario) custo, SUM(produtos_os.idProdutos_os IS NOT NULL AND produtos_os.custo_unitario IS NULL) sem_custo', false)
                ->from('os')->join('produtos_os', 'produtos_os.os_id = os.idOs', 'left')->where('os.concluida_em >=', $since)->where('os.faturado', 1)
                ->group_by('os.idOs, os.valorTotal, os.valor_desconto, os.tipo_desconto')->order_by('os.concluida_em', 'desc')->limit(100)->get()->result();
            foreach ($orders as $order) {
                $net = $order->tipo_desconto ? $order->valor_desconto : $order->valorTotal;
                $margin[] = ['id' => $order->idOs, 'receita' => $net, 'custo' => $order->custo ?? 0, 'faltante' => $order->sem_custo ?? 0, 'saldo' => $net - ($order->custo ?? 0)];
            }
        }

        return compact('backlog', 'late', 'completed', 'technicians', 'margin', 'days');
    }
}
