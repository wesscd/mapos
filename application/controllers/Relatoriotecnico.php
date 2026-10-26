<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Relatoriotecnico extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        foreach (['vOs', 'vCliente'] as $permission) {
            if (! $this->permission->checkPermission($this->session->userdata('permissao'), $permission)) {
                show_error('É necessário acesso às OS e aos clientes para preparar o relatório.', 403);
            }
        }
        $this->load->library('relatorio_tecnico_service');
        $this->load->helper('form');
        $this->output->set_header('Cache-Control: private, no-store');
    }

    private function source($id)
    {
        if (! ctype_digit((string) $id) || (int) $id < 1) {
            show_404();
        }
        try {
            return $this->relatorio_tecnico_service->source((int) $id);
        } catch (DomainException $error) {
            show_404();
        }
    }

    public function index()
    {
        redirect('os');
    }

    public function preparar($id = null)
    {
        $source = $this->source($id);
        $input = (array) $this->input->post();
        if ($this->input->method() === 'post' && $this->input->post('acao') !== 'editar') {
            try {
                $report = $this->relatorio_tecnico_service->prepare((int) $id, $input);
                $key = bin2hex(random_bytes(24));
                $this->session->set_userdata('relatorio_previa', ['chave' => $key, 'expira' => time() + 1800, 'relatorio' => $report]);
                $this->data['report'] = $report;
                $this->data['previewKey'] = $key;
                $this->data['view'] = 'relatorios/previa';
                return $this->layout();
            } catch (DomainException $error) {
                $this->data['reportError'] = $error->getMessage();
            }
        }
        $values = [];
        foreach (['problema', 'diagnostico', 'recomendacoes'] as $field) {
            $values[$field] = is_string($input[$field] ?? null) ? $input[$field] : $source[$field];
        }
        foreach (['resultados', 'fotos'] as $field) {
            $submitted = is_array($input[$field] ?? null) ? $input[$field] : [];
            $values[$field] = $input ? array_values(array_filter($submitted, fn ($value) => is_scalar($value) && isset($source[$field][(string) $value]))) : ($field === 'resultados' ? array_keys($source[$field]) : []);
        }
        $this->data['source'] = $source;
        $this->data['values'] = $values;
        $this->data['view'] = 'relatorios/editor';
        $this->layout();
    }

    public function pdf($id = null)
    {
        if ($this->input->method() !== 'post') {
            show_error('Gere a prévia antes de baixar o relatório.', 405);
        }
        $source = $this->source($id);
        $preview = $this->session->userdata('relatorio_previa');
        $key = $this->input->post('previa');
        if (! is_array($preview) || ! is_string($key) || ! hash_equals($preview['chave'], $key) || $preview['expira'] < time() || $preview['relatorio']['os'] !== (int) $id || ! hash_equals($source['hash'], $preview['relatorio']['hash'])) {
            show_error('A prévia expirou ou a OS mudou. Prepare e confira novamente o relatório.', 409);
        }
        if ($this->input->post('conferido') !== '1') {
            show_error('Confirme a revisão do conteúdo antes de baixar.', 422);
        }
        $bytes = $this->relatorio_tecnico_service->pdf($preview['relatorio']);
        $this->output->set_content_type('application/pdf')->set_header('X-Content-Type-Options: nosniff')->set_header('Content-Disposition: attachment; filename="relatorio-tecnico-os-' . (int) $id . '.pdf"')->set_output($bytes);
    }
}
