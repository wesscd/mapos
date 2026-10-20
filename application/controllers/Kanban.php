<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Kanban extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
            show_error('Você não tem permissão para visualizar as OS.', 403);
        }
        $this->load->model('kanban_model');
        $this->load->helper('form');
    }

    public function index()
    {
        $query = $this->input->get('q');
        $query = is_string($query) ? mb_substr(trim($query), 0, 100) : '';
        $technician = max(0, (int) $this->input->get('tecnico'));
        $closed = $this->input->get('encerradas') === '1';
        $this->data['board'] = $this->kanban_model->board($query, $technician, $closed);
        $this->data['query'] = $query;
        $this->data['technician'] = $technician;
        $this->data['closed'] = $closed;
        $this->data['technicians'] = $this->db->select('idUsuarios, nome')->order_by('nome')->get('usuarios')->result();
        $this->data['canMove'] = $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs');
        $this->data['menuKanban'] = true;
        $this->data['view'] = 'os/kanban';
        $this->layout();
    }

    public function mover($id = null)
    {
        if ($this->input->method() !== 'post' || ! ctype_digit((string) $id)
            || ! $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs')) {
            show_error('Operação não permitida.', 403);
        }
        $this->load->library('os_workflow');
        try {
            $this->os_workflow->transition((int) $id, $this->input->post('status'), $this->input->post('status_anterior'), ! empty($this->data['configuration']['control_estoque']));
            $this->session->set_flashdata('success', 'Etapa atualizada. Histórico e avisos seguem as configurações da OS.');
        } catch (DomainException $error) {
            $this->session->set_flashdata('error', $error->getMessage());
        }
        $query = $this->input->post('q');
        $filters = ['q' => is_string($query) ? mb_substr($query, 0, 100) : '', 'tecnico' => max(0, (int) $this->input->post('tecnico')), 'encerradas' => $this->input->post('encerradas') === '1' ? '1' : '0'];
        redirect('kanban?' . http_build_query($filters));
    }
}
