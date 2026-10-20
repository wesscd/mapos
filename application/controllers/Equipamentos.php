<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Equipamentos extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('equipamento_service');
        $this->load->helper('form');
        $this->data['menuEquipamentos'] = true;
    }

    private function permission($name)
    {
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), $name)) {
            show_error('Você não tem permissão para esta operação.', 403);
        }
    }

    public function index()
    {
        $this->permission('vCliente');
        $query = $this->input->get('q');
        $query = is_string($query) ? mb_substr(trim($query), 0, 100) : '';
        $page = max(0, min(100000, (int) $this->input->get('pagina')));
        $this->db->select('equipamentos.*, clientes.nomeCliente, marcas.marca')->from('equipamentos')
            ->join('clientes', 'clientes.idClientes = equipamentos.clientes_id')
            ->join('marcas', 'marcas.idMarcas = equipamentos.marcas_id', 'left');
        if ($query !== '') {
            $this->db->group_start()->like('equipamento', $query)->or_like('num_serie', $query)
                ->or_like('modelo', $query)->or_like('nomeCliente', $query)->group_end();
        }
        $this->data['items'] = $this->db->order_by('idEquipamentos', 'desc')->limit(31, $page * 30)->get()->result();
        $this->data['query'] = $query;
        $this->data['page'] = $page;
        $this->data['view'] = 'equipamentos/lista';
        $this->layout();
    }

    public function salvar($id = null)
    {
        $this->permission($id ? 'eCliente' : 'aCliente');
        $equipment = $id ? $this->equipamento_service->get((int) $id) : null;
        if ($id && ! $equipment) {
            show_404();
        }
        $this->data['error'] = '';
        if ($this->input->method() === 'post') {
            try {
                $saved = $this->equipamento_service->save($this->input->post(), $id ? (int) $id : null);
                $this->session->set_flashdata('success', 'Equipamento salvo.');
                redirect('equipamentos/visualizar/' . $saved);
            } catch (DomainException $error) {
                $this->data['error'] = $error->getMessage();
                $equipment = (object) $this->input->post();
            }
        }
        // Search customers instead of sending the entire customer base to the browser.
        $customerId = (int) ($equipment->clientes_id ?? $this->input->get('cliente'));
        $customerQuery = $this->input->get('cliente_busca');
        $customerQuery = is_string($customerQuery) ? mb_substr(trim($customerQuery), 0, 100) : '';
        if ($customerQuery !== '') {
            $this->db->like('nomeCliente', $customerQuery);
        } elseif ($customerId) {
            $this->db->where('idClientes', $customerId);
        }
        $this->data['customers'] = $this->db->select('idClientes, nomeCliente')->order_by('nomeCliente')->limit(50)->get('clientes')->result();
        $this->data['equipment'] = $equipment;
        $this->data['selectedCustomer'] = $customerId;
        $this->data['view'] = 'equipamentos/formulario';
        $this->layout();
    }

    public function visualizar($id = null)
    {
        $this->permission('vCliente');
        $equipment = $this->equipamento_service->get((int) $id);
        if (! $equipment) {
            show_404();
        }
        $this->data['equipment'] = $equipment;
        $this->data['photos'] = $this->equipamento_service->photos($id);
        if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vOs')) {
            $this->data['history'] = $this->db->select('os.idOs, os.status, os.dataInicial, os.entregue_em')->from('equipamentos_os')
                ->join('os', 'os.idOs = equipamentos_os.os_id')->where('equipamentos_id', (int) $id)->order_by('idOs', 'desc')->limit(100)->get()->result();
        }
        $this->data['view'] = 'equipamentos/detalhes';
        $this->layout();
    }

    public function foto($id = null)
    {
        $this->permission('vCliente');
        $photo = $this->db->get_where('equipamento_fotos', ['id' => (int) $id])->row();
        if (! $photo) {
            show_404();
        }
        $this->output->set_header('Cache-Control: private, no-store')->set_header('X-Content-Type-Options: nosniff')
            ->set_content_type('image/jpeg')->set_output($photo->imagem);
    }

    public function adicionarFoto($id = null)
    {
        $this->permission('eCliente');
        if ($this->input->method() !== 'post') {
            show_error('Use o formulário de envio.', 405);
        }
        try {
            $file = $_FILES['foto'] ?? [];
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ! is_uploaded_file($file['tmp_name'] ?? '') || ($file['size'] ?? 0) > 2097152) {
                throw new DomainException('Selecione uma foto JPEG ou PNG de até 2 MB.');
            }
            $this->equipamento_service->photo((int) $id, file_get_contents($file['tmp_name']));
            $this->session->set_flashdata('success', 'Foto registrada.');
        } catch (DomainException $error) {
            $this->session->set_flashdata('error', $error->getMessage());
        }
        redirect('equipamentos/visualizar/' . (int) $id);
    }

    public function vincular($orderId = null)
    {
        $this->permission('eOs');
        $this->permission('vCliente');
        $this->orderAction((int) $orderId, fn () => $this->equipamento_service->link((int) $orderId, (int) $this->input->post('equipamento_id')));
    }

    public function receber($orderId = null)
    {
        $this->permission('eOs');
        $this->permission('vCliente');
        $this->orderAction((int) $orderId, fn () => $this->equipamento_service->intake((int) $orderId, (int) $this->input->post('vinculo_id'), $this->input->post()));
    }

    private function orderAction($id, callable $work)
    {
        if ($this->input->method() !== 'post') {
            show_error('Use o formulário da OS.', 405);
        }
        try {
            $work();
            $this->session->set_flashdata('success', 'Registro salvo.');
        } catch (DomainException $error) {
            $this->session->set_flashdata('error', $error->getMessage());
        }
        redirect('os/visualizar/' . $id . '#equipamentos');
    }

    public function comprovante($id = null)
    {
        $this->permission('vOs');
        $this->permission('vCliente');
        $receipt = $this->db->get_where('equipamento_recibos', ['id' => (int) $id])->row();
        if (! $receipt) {
            show_404();
        }
        $this->output->set_header('Cache-Control: private, no-store');
        $this->load->view('equipamentos/comprovante', ['receipt' => $receipt, 'snapshot' => json_decode($receipt->dados, true)]);
    }

    public function etiqueta($id = null)
    {
        $this->permission('vCliente');
        $equipment = $this->equipamento_service->get((int) $id);
        if (! $equipment) {
            show_404();
        }
        $qr = new \Mpdf\QrCode\QrCode(site_url('equipamentos/visualizar/' . (int) $id));
        $png = (new \Mpdf\QrCode\Output\Png())->output($qr, 240);
        $this->output->set_header('Cache-Control: private, no-store');
        $this->load->view('equipamentos/etiqueta', ['equipment' => $equipment, 'qr' => base64_encode($png)]);
    }
}
