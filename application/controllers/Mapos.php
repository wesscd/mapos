<?php if (!defined('BASEPATH')) { exit('No direct script access allowed'); }
class Mapos extends MY_Controller {
    public function __construct()
    {
        parent::__construct();
        $this->load->model('mapos_model');
    }

    public function index()
    {
        $status = array('Em Andamento', 'Aguardando Peças');
        $this->data['ordens_status'] = $this->mapos_model->getOsStatus($status);
        $vstatus = array('Aberto', 'Em Andamento', 'Aguardando Peças', 'Aprovado', 'Orçamento');
        $this->data['vendasstatus'] = $this->mapos_model->getVendasStatus($vstatus);
        $this->data['lancamentos'] = $this->mapos_model->getLancamentos();
        $this->data['ordens_orcamentos'] = $this->mapos_model->getOsOrcamentos();
        $this->data['ordens_abertas'] = $this->mapos_model->getOsAbertas();
        $this->data['ordens_aprovadas'] = $this->mapos_model->getOsAprovadas();
        $this->data['ordens_finalizadas'] = $this->mapos_model->getOsFinalizadas();
        $this->data['ordens_aguardando'] = $this->mapos_model->getOsAguardandoPecas();
        $this->data['ordens_andamento'] = $this->mapos_model->getOsAndamento();
        $this->data['produtos'] = $this->mapos_model->getProdutosMinimo();
        $this->data['os'] = $this->mapos_model->getOsEstatisticas();
        $this->data['estatisticas_financeiro'] = $this->mapos_model->getEstatisticasFinanceiro();
        $this->data['financeiro_mes_dia'] = $this->mapos_model->getEstatisticasFinanceiroDia($this->input->get('year'));
        $this->data['financeiro_mes'] = $this->mapos_model->getEstatisticasFinanceiroMes($this->input->get('year'));
        $this->data['financeiro_mesinadipl'] = $this->mapos_model->getEstatisticasFinanceiroMesInadimplencia($this->input->get('year'));
        $this->data['menuPainel'] = 'Painel';
        $this->data['view'] = 'mapos/painel';

        return $this->layout();
    }

    public function minhaConta()
    {
        $this->data['usuario'] = $this->mapos_model->getById($this->session->userdata('id_admin'));
        $this->data['view'] = 'mapos/minhaConta';

        return $this->layout();
    }

    public function alterarSenha()
    {
        $current_user = $this->mapos_model->getById($this->session->userdata('id_admin'));

        if (!$current_user) {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao pesquisar usuário!');
            redirect(site_url('mapos/minhaConta'));
        }

        $oldSenha = $this->input->post('oldSenha');
        $senha = $this->input->post('novaSenha');

        if (!password_verify($oldSenha, $current_user->senha)) {
            $this->session->set_flashdata('error', 'A senha atual não corresponde com a senha informada.');
            redirect(site_url('mapos/minhaConta'));
        }

        $result = $this->mapos_model->alterarSenha($senha);

        if ($result) {
            $this->session->set_flashdata('success', 'Senha alterada com sucesso!');
            redirect(site_url('mapos/minhaConta'));
        }

        $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar alterar a senha!');
        redirect(site_url('mapos/minhaConta'));
    }

    public function pesquisar()
    {
        $termo = $this->input->get('termo');

        $data['results'] = $this->mapos_model->pesquisar($termo);
        $this->data['produtos'] = $data['results']['produtos'];
        $this->data['servicos'] = $data['results']['servicos'];
        $this->data['os'] = $data['results']['os'];
        $this->data['clientes'] = $data['results']['clientes'];
        $this->data['view'] = 'mapos/pesquisa';

        return $this->layout();
    }

    public function backup()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cBackup')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para efetuar backup.');
            redirect(base_url());
        }

        $this->load->dbutil();
        $prefs = [
            'format' => 'zip',
            'foreign_key_checks' => false,
            'filename' => 'backup' . date('d-m-Y') . '.sql',
        ];

        $backup = $this->dbutil->backup($prefs);

        $this->load->helper('file');
        write_file(base_url() . 'backup/backup.zip', $backup);

        log_info('Efetuou backup do banco de dados.');

        $this->load->helper('download');
        force_download('backup' . date('d-m-Y H:m:s') . '.zip', $backup);
    }

    public function emitente()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmitente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar emitente.');
            redirect(base_url());
        }

        $this->data['menuConfiguracoes'] = 'Configuracoes';
        $this->data['dados'] = $this->mapos_model->getEmitente();
        $this->data['view'] = 'mapos/emitente';

        return $this->layout();
    }

    public function do_upload()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmitente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar emitente.');
            redirect(base_url());
        }

        $this->load->library('upload');

        $image_upload_folder = FCPATH . 'assets/uploads';

        if (!file_exists($image_upload_folder)) {
            mkdir($image_upload_folder, DIR_WRITE_MODE, true);
        }

        $this->upload_config = [
            'upload_path' => $image_upload_folder,
            'allowed_types' => 'png|jpg|jpeg|bmp|svg',
            'max_size' => 2048,
            'remove_space' => true,
            'encrypt_name' => true,
        ];

        $this->upload->initialize($this->upload_config);

        if (!$this->upload->do_upload()) {
            $upload_error = $this->upload->display_errors();
            print_r($upload_error);
            exit();
        } else {
            $file_info = [$this->upload->data()];

            return $file_info[0]['file_name'];
        }
    }

    public function do_upload_user()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmitente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar emitente.');
            redirect(base_url());
        }

        $this->load->library('upload');

        $image_upload_folder = FCPATH . 'assets/userImage/';

        if (!file_exists($image_upload_folder)) {
            mkdir($image_upload_folder, DIR_WRITE_MODE, true);
        }

        $this->upload_config = [
            'upload_path' => $image_upload_folder,
            'allowed_types' => 'png|jpg|jpeg|bmp',
            'max_size' => 2048,
            'remove_space' => true,
            'encrypt_name' => true,
        ];

        $this->upload->initialize($this->upload_config);

        if (!$this->upload->do_upload()) {
            $upload_error = $this->upload->display_errors();
            print_r($upload_error);
            exit();
        } else {
            $file_info = [$this->upload->data()];

            return $file_info[0]['file_name'];
        }
    }

    public function cadastrarEmitente()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmitente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar emitente.');
            redirect(base_url());
        }

        $this->load->library('form_validation');
        $this->form_validation->set_rules('nome', 'Razão Social', 'required|trim');
        $this->form_validation->set_rules('cnpj', 'CNPJ', 'required|trim');
        $this->form_validation->set_rules('ie', 'IE', 'trim');
        $this->form_validation->set_rules('cep', 'CEP', 'required|trim');
        $this->form_validation->set_rules('logradouro', 'Logradouro', 'required|trim');
        $this->form_validation->set_rules('numero', 'Número', 'required|trim');
        $this->form_validation->set_rules('bairro', 'Bairro', 'required|trim');
        $this->form_validation->set_rules('cidade', 'Cidade', 'required|trim');
        $this->form_validation->set_rules('uf', 'UF', 'required|trim');
        $this->form_validation->set_rules('telefone', 'Telefone', 'required|trim');
        $this->form_validation->set_rules('email', 'E-mail', 'required|trim');

        if ($this->form_validation->run() == false) {
            $this->session->set_flashdata('error', 'Campos obrigatórios não foram preenchidos.');
            redirect(site_url('mapos/emitente'));
        } else {
            $nome = $this->input->post('nome');
            $cnpj = $this->input->post('cnpj');
            $ie = $this->input->post('ie');
            $cep = $this->input->post('cep');
            $logradouro = $this->input->post('logradouro');
            $numero = $this->input->post('numero');
            $bairro = $this->input->post('bairro');
            $cidade = $this->input->post('cidade');
            $uf = $this->input->post('uf');
            $telefone = $this->input->post('telefone');
            $email = $this->input->post('email');
            $image = $this->do_upload();
            $logo = base_url() . 'assets/uploads/' . $image;

            $retorno = $this->mapos_model->addEmitente($nome, $cnpj, $ie, $cep, $logradouro, $numero, $bairro, $cidade, $uf, $telefone, $email, $logo);
            if ($retorno) {
                $this->session->set_flashdata('success', 'As informações foram inseridas com sucesso.');
                log_info('Adicionou informações de emitente.');
            } else {
                $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar inserir as informações.');
            }
            redirect(site_url('mapos/emitente'));
        }
    }

    public function editarEmitente()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmitente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar emitente.');
            redirect(base_url());
        }

        $this->load->library('form_validation');
        $this->form_validation->set_rules('nome', 'Razão Social', 'required|trim');
        $this->form_validation->set_rules('cnpj', 'CNPJ', 'required|trim');
        $this->form_validation->set_rules('ie', 'IE', 'trim');
        $this->form_validation->set_rules('cep', 'CEP', 'required|trim');
        $this->form_validation->set_rules('logradouro', 'Logradouro', 'required|trim');
        $this->form_validation->set_rules('numero', 'Número', 'required|trim');
        $this->form_validation->set_rules('bairro', 'Bairro', 'required|trim');
        $this->form_validation->set_rules('cidade', 'Cidade', 'required|trim');
        $this->form_validation->set_rules('uf', 'UF', 'required|trim');
        $this->form_validation->set_rules('telefone', 'Telefone', 'required|trim');
        $this->form_validation->set_rules('email', 'E-mail', 'required|trim');

        if ($this->form_validation->run() == false) {
            $this->session->set_flashdata('error', 'Campos obrigatórios não foram preenchidos.');
            redirect(site_url('mapos/emitente'));
        } else {
            $nome = $this->input->post('nome');
            $cnpj = $this->input->post('cnpj');
            $ie = $this->input->post('ie');
            $cep = $this->input->post('cep');
            $logradouro = $this->input->post('logradouro');
            $numero = $this->input->post('numero');
            $bairro = $this->input->post('bairro');
            $cidade = $this->input->post('cidade');
            $uf = $this->input->post('uf');
            $telefone = $this->input->post('telefone');
            $email = $this->input->post('email');
            $id = $this->input->post('id');

            $retorno = $this->mapos_model->editEmitente($id, $nome, $cnpj, $ie, $cep, $logradouro, $numero, $bairro, $cidade, $uf, $telefone, $email);
            if ($retorno) {
                $this->session->set_flashdata('success', 'As informações foram alteradas com sucesso.');
                log_info('Alterou informações de emitente.');
            } else {
                $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar alterar as informações.');
            }
            redirect(site_url('mapos/emitente'));
        }
    }

    public function editarLogo()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmitente')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar emitente.');
            redirect(base_url());
        }

        $id = $this->input->post('id');
        if ($id == null || !is_numeric($id)) {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar alterar a logomarca.');
            redirect(site_url('mapos/emitente'));
        }
        $this->load->helper('file');
        delete_files(FCPATH . 'assets/uploads/');

        $image = $this->do_upload();
        $logo = base_url() . 'assets/uploads/' . $image;

        $retorno = $this->mapos_model->editLogo($id, $logo);
        if ($retorno) {
            $this->session->set_flashdata('success', 'As informações foram alteradas com sucesso.');
            log_info('Alterou a logomarca do emitente.');
        } else {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar alterar as informações.');
        }
        redirect(site_url('mapos/emitente'));
    }

    public function uploadUserImage()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cUsuario')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para mudar a foto.');
            redirect(base_url());
        }

        $id = $this->session->userdata('id_admin');
        if ($id == null || !is_numeric($id)) {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar alterar sua foto.');
            redirect(site_url('mapos/minhaConta'));
        }

        $usuario = $this->mapos_model->getById($id);

        if (is_file(FCPATH . 'assets/userImage/' . $usuario->url_image_user)) {
            unlink(FCPATH . 'assets/userImage/' . $usuario->url_image_user);
        }

        $image = $this->do_upload_user();
        $imageUserPath = $image;
        $retorno = $this->mapos_model->editImageUser($id, $imageUserPath);

        if ($retorno) {
            $this->session->set_userdata('url_image_user', $imageUserPath);
            $this->session->set_flashdata('success', 'Foto alterada com sucesso.');
            log_info('Alterou a Imagem do Usuario.');
        } else {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao tentar alterar sua foto.');
        }
        redirect(site_url('mapos/minhaConta'));
    }

    public function emails()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmail')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para visualizar fila de e-mails');
            redirect(base_url());
        }

        $this->data['menuConfiguracoes'] = 'Email';

        $this->load->library('pagination');
        $this->load->model('email_model');

        $this->data['configuration']['base_url'] = site_url('mapos/emails/');
        $this->data['configuration']['total_rows'] = $this->email_model->count('email_queue');

        $this->pagination->initialize($this->data['configuration']);

        $this->data['results'] = $this->email_model->get('email_queue', '*', '', $this->data['configuration']['per_page'], $this->uri->segment(3));

        $this->data['view'] = 'emails/emails';

        return $this->layout();
    }

    public function excluirEmail()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cEmail')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para excluir e-mail da fila.');
            redirect(base_url());
        }

        $id = $this->input->post('id');
        if ($id == null) {
            $this->session->set_flashdata('error', 'Erro ao tentar excluir e-mail da fila.');
            redirect(site_url('mapos/emails/'));
        }

        $this->load->model('email_model');
        $this->email_model->delete('email_queue', 'id', $id);

        log_info('Removeu um e-mail da fila de envio. ID: ' . $id);

        $this->session->set_flashdata('success', 'E-mail removido da fila de envio!');
        redirect(site_url('mapos/emails/'));
    }

    public function configurar()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para configurar o sistema.');
            redirect(base_url());
        }

        $this->load->library('form_validation');
        $this->data['custom_error'] = '';

        // Log para depurar os dados recebidos do formulário
        log_message('debug', 'Dados recebidos do formulário: ' . print_r($this->input->post(), true));

        if ($this->input->post('test_whatsapp') == '1') {
            $this->load->library('mapos_whatsapp');
            $result = $this->mapos_whatsapp->send($this->input->post('test_number'), $this->input->post('test_message'));

            if ($result === true) {
                $this->data['test_message'] = '<div class="alert alert-success"><strong>Sucesso!</strong> Mensagem enviada com sucesso.</div>';
            } else {
                $this->data['test_message'] = '<div class="alert alert-danger"><strong>Erro!</strong> Não foi possível enviar a mensagem: ' . $result . '</div>';
            }
        }

        // Definir regras de validação apenas para campos obrigatórios
        $this->form_validation->set_rules('app_name', 'Nome do Sistema', 'required|trim');
        $this->form_validation->set_rules('per_page', 'Itens por Página', 'required|numeric|trim');

        if ($this->form_validation->run('configuracoes') == false) {
            $this->data['custom_error'] = (validation_errors() ? '<div class="form_error">' . validation_errors() . '</div>' : false);
            log_message('error', 'Erro de validação do formulário: ' . validation_errors());
        } else {
            $data = array(
                'app_name' => $this->input->post('app_name'),
                'telefone_empresa' => $this->input->post('telefone_empresa'),
                'use_whatsapp_empresa' => $this->input->post('use_whatsapp_empresa'),
                'app_theme' => $this->input->post('app_theme'),
                'per_page' => $this->input->post('per_page'),
                'control_datatable' => $this->input->post('control_datatable'),
                'control_baixa' => $this->input->post('control_baixa'),
                'control_editos' => $this->input->post('control_editos'),
                'control_edit_vendas' => $this->input->post('control_edit_vendas'),
                'pix_key' => $this->input->post('pix_key'),
                'control_estoque' => $this->input->post('control_estoque'),
                'whatsapp_enabled' => $this->input->post('whatsapp_enabled'),
                'whatsapp_api_url' => $this->input->post('whatsapp_api_url'),
                'whatsapp_number' => $this->input->post('whatsapp_number'),
                'whatsapp_api_token' => $this->input->post('whatsapp_api_token'),
                'os_notification' => $this->input->post('os_notification'),
                'notifica_whats' => $this->input->post('notifica_whats'),
                'notifica_whats_criac' => $this->input->post('notifica_whats_criac'),
                'whatsapp_cad_msg' => $this->input->post('whatsapp_cad_msg'),
                'control_2vias' => $this->input->post('control_2vias'),
                'os_status_list' => json_encode($this->input->post('os_status_list')),
                'email_automatico' => $this->input->post('email_automatico'),
            );

            // Log para verificar os dados enviados ao modelo
            log_message('debug', 'Dados enviados ao modelo: ' . print_r($data, true));

            // Atualizar variáveis de ambiente
            $envVars = array(
                'PAYMENT_GATEWAYS_EFI_PRODUCTION' => $this->input->post('PAYMENT_GATEWAYS_EFI_PRODUCTION'),
                'PAYMENT_GATEWAYS_EFI_CREDENTIAIS_CLIENT_ID' => $this->input->post('PAYMENT_GATEWAYS_EFI_CREDENTIAIS_CLIENT_ID'),
                'PAYMENT_GATEWAYS_EFI_CREDENTIAIS_CLIENT_SECRET' => $this->input->post('PAYMENT_GATEWAYS_EFI_CREDENTIAIS_CLIENT_SECRET'),
                'PAYMENT_GATEWAYS_EFI_BOLETO_EXPIRATION' => $this->input->post('PAYMENT_GATEWAYS_EFI_BOLETO_EXPIRATION'),
                'PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_PUBLIC_KEY' => $this->input->post('PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_PUBLIC_KEY'),
                'PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_ACCESS_TOKEN' => $this->input->post('PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_ACCESS_TOKEN'),
                'PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_CLIENT_ID' => $this->input->post('PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_CLIENT_ID'),
                'PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_CLIENT_SECRET' => $this->input->post('PAYMENT_GATEWAYS_MERCADO_PAGO_CREDENTIALS_CLIENT_SECRET'),
                'PAYMENT_GATEWAYS_MERCADO_PAGO_BOLETO_EXPIRATION' => $this->input->post('PAYMENT_GATEWAYS_MERCADO_PAGO_BOLETO_EXPIRATION'),
                'PAYMENT_GATEWAYS_ASAAS_PRODUCTION' => $this->input->post('PAYMENT_GATEWAYS_ASAAS_PRODUCTION'),
                'PAYMENT_GATEWAYS_ASAAS_NOTIFY' => $this->input->post('PAYMENT_GATEWAYS_ASAAS_NOTIFY'),
                'PAYMENT_GATEWAYS_ASAAS_CREDENTIAIS_API_KEY' => $this->input->post('PAYMENT_GATEWAYS_ASAAS_CREDENTIAIS_API_KEY'),
                'PAYMENT_GATEWAYS_ASAAS_BOLETO_EXPIRATION' => $this->input->post('PAYMENT_GATEWAYS_ASAAS_BOLETO_EXPIRATION'),
                'IMPRIMIR_ANEXOS' => $this->input->post('imprmirAnexos'),
                'API_ENABLED' => $this->input->post('apiEnabled'),
                'API_TOKEN_EXPIRE_TIME' => $this->input->post('apiExpireTime'),
                'EMAIL_PROTOCOL' => $this->input->post('EMAIL_PROTOCOL'),
                'EMAIL_SMTP_HOST' => $this->input->post('EMAIL_SMTP_HOST'),
                'EMAIL_SMTP_CRYPTO' => $this->input->post('EMAIL_SMTP_CRYPTO'),
                'EMAIL_SMTP_PORT' => $this->input->post('EMAIL_SMTP_PORT'),
                'EMAIL_SMTP_USER' => $this->input->post('EMAIL_SMTP_USER'),
                'EMAIL_SMTP_PASS' => $this->input->post('EMAIL_SMTP_PASS'),
            );

            // Resetar token JWT se solicitado
            if ($this->input->post('resetJwtToken') == 'sim') {
                $this->mapos_model->resetJwtToken();
            }

            // Salvar configurações
            $retorno = $this->mapos_model->saveConfiguracao($data, $envVars);

            // Log para verificar o resultado do modelo
            log_message('debug', 'Resultado do saveConfiguracao: ' . ($retorno ? 'Sucesso' : 'Falha'));

            if ($retorno) {
                $this->session->set_flashdata('success', 'Configurações salvas com sucesso!');
                log_info('Alterou configurações do sistema.');
            } else {
                $this->session->set_flashdata('error', 'Ocorreu um erro ao salvar as configurações.');
            }
            redirect(site_url('mapos/configurar'));
        }

        $this->data['configuration'] = $this->mapos_model->getConfiguracao();
        $this->data['menuConfiguracoes'] = 'Configuracoes';
        $this->data['view'] = 'mapos/configurar';

        return $this->layout();
    }

    public function atualizarBanco()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para atualizar o banco de dados.');
            redirect(base_url());
        }

        $this->load->model('migrations_model');
        $result = $this->migrations_model->run_migrations();

        if ($result) {
            $this->session->set_flashdata('success', 'Banco de dados atualizado com sucesso!');
            log_info('Atualizou o banco de dados.');
        } else {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao atualizar o banco de dados.');
        }
        redirect(site_url('mapos/configurar'));
    }

    public function atualizarMapos()
    {
        if (!$this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema')) {
            $this->session->set_flashdata('error', 'Você não tem permissão para atualizar o sistema.');
            redirect(base_url());
        }

        $this->load->library('mapos_update');
        $result = $this->mapos_update->update();

        if ($result) {
            $this->session->set_flashdata('success', 'Sistema atualizado com sucesso!');
            log_info('Atualizou o sistema Mapos.');
        } else {
            $this->session->set_flashdata('error', 'Ocorreu um erro ao atualizar o sistema.');
        }
        redirect(site_url('mapos/configurar'));
    }
}