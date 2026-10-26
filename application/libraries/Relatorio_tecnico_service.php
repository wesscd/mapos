<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Relatorio_tecnico_service
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->library(['os_workflow', 'checklist_service']);
    }

    public function source($id)
    {
        $order = $this->ci->db->select('os.idOs, os.status, os.defeito, os.laudoTecnico, clientes.nomeCliente, usuarios.nome AS tecnico')->from('os')->join('clientes', 'clientes.idClientes = os.clientes_id')->join('usuarios', 'usuarios.idUsuarios = os.usuarios_id')->where('os.idOs', (int) $id)->get()->row_array();
        if (! $order) {
            throw new DomainException('Ordem de serviço não encontrada.');
        }
        $company = $this->ci->db->select('nome')->get('emitente')->row();
        $source = ['os' => (int) $id, 'cliente' => $order['nomeCliente'], 'tecnico' => $order['tecnico'], 'empresa' => $company->nome ?? 'Barão Tech', 'status' => $order['status'], 'problema' => $this->plain($order['defeito']), 'diagnostico' => $this->plain($order['laudoTecnico']), 'recomendacoes' => '', 'aprovacao' => $this->ci->os_workflow->approvedBudget($id)];
        $source['equipamentos'] = $this->ci->db->select('equipamentos.equipamento, marcas.marca, equipamentos.modelo, equipamentos.num_serie')->from('equipamentos')->join('marcas', 'marcas.idMarcas = equipamentos.marcas_id', 'left')->join('equipamentos_os', 'equipamentos_os.equipamentos_id = equipamentos.idEquipamentos')->where('equipamentos_os.os_id', (int) $id)->order_by('equipamentos.idEquipamentos')->get()->result_array();
        $source['resultados'] = [];
        foreach ($this->ci->checklist_service->details($id) as $group) {
            foreach ($group->itens as $item) {
                if ($item->resultado === 'pendente') {
                    continue;
                }
                $source['resultados'][(string) $item->id] = ['id' => (int) $item->id, 'versao' => (int) $item->versao, 'equipamento' => $group->equipamento_nome, 'descricao' => $item->descricao, 'resultado' => Checklist_service::RESULTS[$item->fase][$item->resultado], 'secao' => $item->fase === 'execucao' && $item->escopo !== 'verificacao' ? 'procedimentos' : 'testes'];
            }
        }
        $source['fotos'] = [];
        $inspectionPhotos = $this->ci->db->select('os_vistoria_fotos.id, os_vistoria_fotos.perspectiva, os_vistorias.tipo, equipamentos.equipamento')->from('os_vistoria_fotos')->join('os_vistorias', 'os_vistorias.id = os_vistoria_fotos.vistoria_id')->join('equipamentos_os', 'equipamentos_os.idEquipamentos_os = os_vistorias.vinculo_id')->join('equipamentos', 'equipamentos.idEquipamentos = equipamentos_os.equipamentos_id')->where('equipamentos_os.os_id', (int) $id)->order_by('os_vistoria_fotos.id')->get()->result();
        foreach ($inspectionPhotos as $photo) {
            $key = 'v' . $photo->id;
            $source['fotos'][$key] = ['chave' => $key, 'legenda' => $photo->equipamento . ' - ' . ($photo->tipo === 'saida' ? 'Saída' : 'Entrada') . ' - ' . $photo->perspectiva, 'url' => site_url('equipamentos/vistoriaFoto/' . (int) $photo->id)];
        }
        $checkPhotos = $this->ci->db->select('os_check_fotos.id, os_check_itens.descricao, os_check_grupos.equipamento_nome')->from('os_check_fotos')->join('os_check_itens', 'os_check_itens.id = os_check_fotos.item_id')->join('os_check_grupos', 'os_check_grupos.id = os_check_itens.grupo_id')->where('os_check_grupos.os_id', (int) $id)->order_by('os_check_fotos.id')->get()->result();
        foreach ($checkPhotos as $photo) {
            $key = 'c' . $photo->id;
            $source['fotos'][$key] = ['chave' => $key, 'legenda' => $photo->equipamento_nome . ' - ' . $photo->descricao, 'url' => site_url('checklists/foto/' . (int) $photo->id)];
        }
        $source['hash'] = hash('sha256', json_encode($source, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $source;
    }

    private function plain($value)
    {
        $text = preg_replace('~<br\s*/?>|</(?:p|div|li|h[1-6])>~i', "\n", (string) $value);
        return trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function selected($input, array $available, $limit)
    {
        if (! is_array($input) || count($input) > $limit) {
            throw new DomainException('Seleção inválida. Escolha até seis fotos e até 200 resultados.');
        }
        $selected = [];
        foreach ($input as $key) {
            if (! is_scalar($key) || ! isset($available[(string) $key])) {
                throw new DomainException('A seleção não pertence a esta OS. Atualize a prévia.');
            }
            $selected[(string) $key] = $available[(string) $key];
        }
        return $selected;
    }

    public function prepare($id, array $input)
    {
        $source = $this->source($id);
        if (! is_string($input['fonte'] ?? null) || ! hash_equals($source['hash'], $input['fonte'])) {
            throw new DomainException('Os registros da OS mudaram. Atualize a página e revise novamente.');
        }
        foreach (['problema' => 4000, 'diagnostico' => 6000, 'recomendacoes' => 4000] as $field => $max) {
            $value = $input[$field] ?? '';
            if (! is_string($value) || mb_strlen($value) > $max || ($field !== 'recomendacoes' && trim($value) === '')) {
                throw new DomainException('Preencha problema e diagnóstico e respeite os limites dos textos.');
            }
            $source[$field] = trim($value);
        }
        $source['resultados'] = $this->selected($input['resultados'] ?? [], $source['resultados'], 200);
        $source['fotos'] = $this->selected($input['fotos'] ?? [], $source['fotos'], 6);
        $source['gerado_em'] = date('d/m/Y H:i');
        return $source;
    }

    public function photo($id, $key)
    {
        if (! is_string($key) || ! preg_match('/^([vc])([1-9][0-9]*)$/D', $key, $match)) {
            throw new DomainException('Foto inválida.');
        }
        if ($match[1] === 'v') {
            $row = $this->ci->db->select('os_vistoria_fotos.imagem')->from('os_vistoria_fotos')->join('os_vistorias', 'os_vistorias.id = os_vistoria_fotos.vistoria_id')->join('equipamentos_os', 'equipamentos_os.idEquipamentos_os = os_vistorias.vinculo_id')->where('equipamentos_os.os_id', (int) $id)->where('os_vistoria_fotos.id', (int) $match[2])->get()->row();
        } else {
            $row = $this->ci->db->select('os_check_fotos.imagem')->from('os_check_fotos')->join('os_check_itens', 'os_check_itens.id = os_check_fotos.item_id')->join('os_check_grupos', 'os_check_grupos.id = os_check_itens.grupo_id')->where('os_check_grupos.os_id', (int) $id)->where('os_check_fotos.id', (int) $match[2])->get()->row();
        }
        if (! $row) {
            throw new DomainException('Foto indisponível neste atendimento.');
        }
        return $row->imagem;
    }

    public function pdf(array $report)
    {
        $images = [];
        foreach ($report['fotos'] as $key => $photo) {
            $images[$key] = 'data:image/jpeg;base64,' . base64_encode($this->photo($report['os'], $key));
        }
        // CLI checks and PHP-FPM run as different users; each needs a private cache.
        $user = function_exists('posix_geteuid') ? posix_geteuid() : get_current_user();
        $dir = sys_get_temp_dir() . '/mapos-relatorios-' . substr(hash('sha256', APPPATH . ':' . $user), 0, 16);
        if ((! is_dir($dir) && ! @mkdir($dir, 0700, true) && ! is_dir($dir)) || ! is_writable($dir)) {
            throw new RuntimeException('Não foi possível preparar o PDF.');
        }
        $pdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'format' => 'A4', 'tempDir' => $dir, 'default_font' => 'dejavusans', 'margin_left' => 16, 'margin_right' => 16, 'margin_top' => 16, 'margin_bottom' => 20]);
        $pdf->SetTitle('Relatório técnico - OS ' . (int) $report['os']);
        $pdf->SetHTMLFooter('<div style="font-size:9px;color:#647466;text-align:right">OS ' . (int) $report['os'] . ' - {PAGENO} / {nbpg}</div>');
        $pdf->WriteHTML(file_get_contents(FCPATH . 'assets/css/technical-report.css'), \Mpdf\HTMLParserMode::HEADER_CSS);
        $pdf->WriteHTML($this->ci->load->view('relatorios/documento', compact('report', 'images'), true));
        return $pdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }
}
