<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Comunicacao extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema')) {
            show_error('Você não tem permissão para administrar a comunicação.', 403);
        }
        $this->load->library(['comunicacao_service', 'whatsapp_gateway']);
        $this->load->helper('form');
        $this->output->set_header('Cache-Control: no-store');
    }

    public function index()
    {
        $this->data['settings'] = $this->comunicacao_service->config();
        $this->data['events'] = Comunicacao_service::EVENTS;
        $this->data['messages'] = $this->db->order_by('id', 'desc')->limit(100)->get('com_fila')->result();
        $this->data['smtpReady'] = $this->comunicacao_service->smtpReady();
        $this->data['smtpEmail'] = $this->comunicacao_service->smtpConfig()['smtp_user'] ?? '';
        $customer = (int) $this->input->get('cliente');
        $search = mb_substr(trim((string) $this->input->get('busca')), 0, 100);
        $this->data['search'] = $search;
        $this->data['customers'] = [];
        if ($search !== '') {
            $this->db->select('idClientes, nomeCliente');
            if (ctype_digit($search)) {
                $this->db->where('idClientes', (int) $search);
            } else {
                $this->db->like('nomeCliente', $search);
            }
            $this->data['customers'] = $this->db->order_by('nomeCliente')->limit(15)->get('clientes')->result();
        }
        $this->data['customer'] = $customer ? $this->db->get_where('clientes', ['idClientes' => $customer])->row() : null;
        $this->data['preference'] = $customer ? $this->comunicacao_service->preference($customer) : null;
        $this->data['view'] = 'mapos/comunicacao';
        $this->layout();
    }

    public function status()
    {
        $response = $this->whatsapp_gateway->request('GET', '/status');
        $body = $response['code'] === 200 ? $response['data'] : ['state' => 'unavailable'];
        $this->output->set_content_type('application/json')->set_output(json_encode($body));
    }

    public function tentativas($id = null)
    {
        $this->data['attempts'] = $this->db->where('fila_id', (int) $id)->order_by('id', 'desc')->limit(100)->get('com_tentativas')->result();
        $this->data['view'] = 'mapos/comunicacao_tentativas';
        $this->layout();
    }

    public function salvar()
    {
        if ($this->input->method() !== 'post') {
            show_error('Utilize o formulário para esta operação.', 405);
        }
        $customer = 0;
        try {
            switch ($this->input->post('acao')) {
                case 'config':
                    $this->comunicacao_service->saveConfig((array) $this->input->post());
                    break;
                case 'destinatarios':
                    $recipient = $this->input->post('os_notification');
                    if (! in_array($recipient, ['todos', 'cliente', 'tecnico', 'emitente', 'nenhum'], true)) {
                        throw new DomainException('Selecione os destinatários do e-mail manual.');
                    }
                    $this->db->where('config', 'os_notification')->update('configuracoes', ['valor' => $recipient]);
                    break;
                case 'modelo':
                    $this->comunicacao_service->saveTemplate($this->input->post('id'), $this->input->post('texto'), (bool) $this->input->post('ativo'));
                    break;
                case 'preferencia':
                    $customer = (int) $this->input->post('cliente_id');
                    $this->comunicacao_service->savePreference($customer, $this->input->post('canal'), $this->input->post('evidencia'), 'usuario:' . (int) $this->session->userdata('id_admin'));
                    break;
                case 'smtp':
                    $this->comunicacao_service->saveGmail($this->input->post('email'), $this->input->post('senha'));
                    break;
                case 'conectar':
                case 'desconectar':
                    $path = $this->input->post('acao') === 'conectar' ? '/connect' : '/disconnect';
                    $response = $this->whatsapp_gateway->request('POST', $path);
                    if ($response['code'] !== 200) {
                        throw new DomainException('Serviço WhatsApp indisponível ou ocupado. Aguarde e tente novamente.');
                    }
                    break;
                case 'teste':
                    $this->comunicacao_service->testMessage($this->input->post('canal'), $this->input->post('destino'));
                    break;
                case 'mensagem':
                    $this->comunicacao_service->manageJob($this->input->post('id'), $this->input->post('operacao'));
                    break;
                default:
                    throw new DomainException('Ação inválida.');
            }
            if (in_array($this->input->post('acao'), ['config', 'modelo', 'destinatarios'], true)) {
                $this->session->set_flashdata('success', 'Configuração de notificações salva com sucesso. Novos envios usarão os dados atualizados; mensagens já na fila mantêm o texto registrado.');
                redirect('mapos/configurar#menu3');
                return;
            }
            $this->session->set_flashdata('success', $this->input->post('acao') === 'preferencia' ? 'Preferência salva. Volte à OS e atualize a página para conferir e enviar o orçamento.' : 'Solicitação registrada. Consulte a conexão e o histórico abaixo.');
        } catch (DomainException $error) {
            $this->session->set_flashdata('error', $error->getMessage());
        }
        if (in_array($this->input->post('acao'), ['config', 'modelo', 'destinatarios'], true)) {
            redirect('mapos/configurar#menu3');
            return;
        }
        redirect('comunicacao' . ($customer ? '?cliente=' . $customer . '#preferencias-cliente' : ''));
    }
}
