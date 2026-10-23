<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Checklists extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('checklist_service');
        $this->load->helper('form');
        $this->output->set_header('Cache-Control: no-store');
    }

    private function permission($name)
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), $name)) {
            show_error('Você não tem permissão para esta operação.', 403);
        }
    }

    public function index()
    {
        $this->permission('cSistema');
        $this->data['models'] = $this->checklist_service->models();
        $this->data['view'] = 'checklists/modelos';
        $this->layout();
    }

    public function modelo($id = null)
    {
        $this->permission('cSistema');
        if ($this->input->method() !== 'post' || ! ctype_digit((string) $id)) {
            show_error('Use o formulário de modelos.', 405);
        }
        try {
            $this->checklist_service->saveModel((int) $id, (array) $this->input->post());
            $this->session->set_flashdata('success', 'Modelo salvo. Os checklists já aplicados às OS foram preservados.');
        } catch (DomainException $error) {
            $this->session->set_flashdata('error', $error->getMessage());
            $this->session->set_flashdata('checklist_model_draft', ['id' => (int) $id, 'dados' => (array) $this->input->post()]);
        }
        redirect('checklists');
    }

    public function salvar($id = null)
    {
        $this->permission('eOs');
        $this->permission('vOs');
        if ($this->input->method() !== 'post' || ! ctype_digit((string) $id)) {
            show_error('Use o formulário do checklist.', 405);
        }
        try {
            switch ($this->input->post('acao')) {
                case 'aplicar':
                    $this->checklist_service->apply((int) $id, $this->input->post('tipo'), $this->input->post('equipamento_id'));
                    break;
                case 'adicionar':
                    $this->checklist_service->add((int) $id, $this->input->post('grupo_id'), (array) $this->input->post());
                    break;
                case 'item':
                    $photo = $this->checklist_service->upload();
                    if ($photo !== null) {
                        $this->permission('vCliente');
                    }
                    $this->checklist_service->update((int) $id, $this->input->post('item_id'), (array) $this->input->post(), $photo);
                    break;
                default:
                    throw new DomainException('Ação de checklist inválida.');
            }
            $this->session->set_flashdata('success', 'Checklist salvo. O orçamento e os itens cobrados não foram alterados.');
        } catch (DomainException $error) {
            $this->session->set_flashdata('error', $error->getMessage());
            $this->session->set_flashdata('checklist_draft', ['os' => (int) $id, 'dados' => (array) $this->input->post()]);
        }
        redirect(($this->input->post('retorno') === 'editar' ? 'os/editar/' : 'os/visualizar/') . (int) $id . '#os-pane-analise');
    }

    public function foto($id = null)
    {
        $this->permission('vOs');
        $this->permission('vCliente');
        $photo = $this->db->select('os_check_fotos.imagem')->from('os_check_fotos')->join('os_check_itens', 'os_check_itens.id = os_check_fotos.item_id')->join('os_check_grupos', 'os_check_grupos.id = os_check_itens.grupo_id')->join('os', 'os.idOs = os_check_grupos.os_id')->where('os_check_fotos.id', (int) $id)->get()->row();
        if (! $photo) {
            show_404();
        }
        $this->output->set_header('Cache-Control: private, no-store')->set_header('X-Content-Type-Options: nosniff')->set_content_type('image/jpeg')->set_output($photo->imagem);
    }

    public function resumo($id = null)
    {
        $this->permission('vOs');
        $order = $this->db->get_where('os', ['idOs' => (int) $id])->row();
        if (! $order) {
            show_404();
        }
        $this->load->view('checklists/resumo', ['order' => $order, 'groups' => $this->checklist_service->details((int) $id)]);
    }

    public function historico($id = null)
    {
        $this->permission('vOs');
        $item = $this->db->select('os_check_itens.*, os_check_grupos.os_id')->from('os_check_itens')->join('os_check_grupos', 'os_check_grupos.id = os_check_itens.grupo_id')->join('os', 'os.idOs = os_check_grupos.os_id')->where('os_check_itens.id', (int) $id)->get()->row();
        if (! $item) {
            show_404();
        }
        $this->data['item'] = $item;
        $this->data['history'] = $this->db->select('os_check_registros.*, usuarios.nome AS responsavel')->from('os_check_registros')->join('usuarios', 'usuarios.idUsuarios = os_check_registros.usuario_id', 'left')->where('item_id', $item->id)->order_by('os_check_registros.id', 'desc')->limit(100)->get()->result();
        $this->data['view'] = 'checklists/historico';
        $this->layout();
    }
}
