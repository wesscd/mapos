<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Operacoes extends MY_Controller
{
    public function index()
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
            show_error('Você não tem permissão para visualizar as operações.', 403);
        }
        $days = (int) $this->input->get('dias');
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;
        $this->load->model('operacoes_model');
        $this->data['canFinance'] = $this->permission->checkPermission($this->session->userdata('permissao'), 'vLancamento');
        $this->data['operations'] = $this->operacoes_model->summary($days, $this->data['canFinance']);
        $this->data['menuOperacoes'] = true;
        $this->data['view'] = 'mapos/operacoes';
        $this->layout();
    }
}
