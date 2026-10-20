<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Acompanhamento extends MY_Controller
{
    public function salvar($id = null)
    {
        if ($this->input->method() !== 'post' || ! ctype_digit((string) $id)
            || ! $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs')) {
            show_error('Operação não permitida.', 403);
        }
        $this->load->library('os_workflow');
        try {
            $action = $this->input->post('acao');
            if ($action === 'orcamento') {
                $this->os_workflow->requestApproval((int) $id);
                $message = 'Orçamento disponível para aprovação na área do cliente.';
            } elseif ($action === 'entrega') {
                if ($this->db->where('os_id', (int) $id)->count_all_results('equipamentos_os')
                    && ! $this->permission->checkPermission($this->session->userdata('permissao'), 'vCliente')) {
                    show_error('Você não tem permissão para conferir os equipamentos.', 403);
                }
                $this->os_workflow->deliver((int) $id, $this->input->post('retirado_por'), $this->input->post('aceite_entrega') === '1');
                $message = 'Entrega ao cliente registrada.';
            } elseif ($action === 'checklist') {
                $this->os_workflow->checklist((int) $id, $this->input->post('descricao'), $this->input->post('item_id'), $this->input->post('concluido') === '1');
                $message = 'Checklist atualizado.';
            } else {
                throw new DomainException('Ação inválida.');
            }
            $this->session->set_flashdata('success', $message);
        } catch (DomainException $error) {
            $this->session->set_flashdata('error', $error->getMessage());
        }
        redirect('os/visualizar/' . (int) $id . '#acompanhamento');
    }
}
