<?php $this->load->view('os/resources'); ?>
<div class="os-workspace-v2 os-create" data-os-root data-os-base="<?= site_url('os') ?>">
<header class="os-summary"><span class="mapos-eyebrow">NOVA ORDEM DE SERVIÇO</span><h1>Abrir atendimento</h1><p>Cadastre o atendimento e registre a entrada física quando o equipamento estiver na assistência.</p></header>
<div class="os-feedback" data-os-feedback role="status" aria-live="polite" hidden></div>
<?php if (! empty($order_error) || ! empty($custom_error)) { ?><div class="alert alert-error" role="alert"><?= html_escape($order_error ?? 'Confira os campos obrigatórios e as seleções de cliente e responsável.') ?></div><?php } ?>
<form id="formOs" action="<?= current_url() ?>" method="post" enctype="multipart/form-data" data-os-main>
<section class="mapos-card"><h2>1. Dados do atendimento</h2><?php $this->load->view('os/form_fields'); ?></section>
<section class="mapos-card"><h2>2. Equipamento e recebimento</h2>
                                    <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vCliente')) { ?>
                                    <div class="span12" style="padding: 1%; margin-left: 0" id="os-equipment-picker" data-endpoint="<?= site_url('equipamentos/porCliente') ?>" data-new-url="<?= site_url('equipamentos/salvar') ?>" data-selected="<?= is_scalar($this->input->post('equipamento_id')) ? html_escape($this->input->post('equipamento_id')) : '' ?>">
                                        <label for="equipamento_id">Equipamento do cliente (opcional)</label>
                                        <select class="span12" name="equipamento_id" id="equipamento_id" disabled aria-describedby="os-equipment-help"><option value="">Selecione o cliente primeiro</option></select>
                                        <p id="os-equipment-help" role="status" aria-live="polite">Selecione o cliente na lista de sugestões para consultar seus equipamentos.</p>
                                        <p>O vínculo, sozinho, não confirma recebimento físico. Na vistoria abaixo, você pode registrar a entrada agora ou deixar para depois.</p>
                                        <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'aCliente')) { ?><a id="os-equipment-new" class="btn" href="<?= site_url('equipamentos/salvar') ?>" target="_blank" rel="noopener" hidden>Cadastrar equipamento deste cliente</a><?php } ?>
                                        <button id="os-equipment-refresh" class="btn" type="button" hidden>Atualizar equipamentos</button>
                                    </div>
                                    <script src="<?= base_url('assets/js/os-equipment-picker.js') ?>" defer></script>
                                    <?php } ?>

<?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vCliente')) { ?>
<details class="os-inline-action"><summary>Registrar estado e fotos de entrada</summary><?php $this->load->view('equipamentos/vistoria_campos', ['vistoriaCreation' => true, 'vistoriaSaida' => false]); ?><p>Se houver erro, selecione as fotos novamente. A abertura da OS não confirma a entrada física.</p></details>
<?php } else { ?><p>Consulte um usuário com permissão de equipamentos para registrar a entrada.</p><?php } ?></section>
<section class="mapos-card"><details class="os-inline-action"><summary>3. Análise inicial (opcional)</summary><div class="os-field-grid os-text-fields"><?php foreach (['laudoTecnico' => 'Diagnóstico técnico', 'observacoes' => 'Observações da execução'] as $field => $label) { ?><div><label for="<?= html_escape($field) ?>"><?= html_escape($label) ?></label><textarea class="editor" id="<?= html_escape($field) ?>" name="<?= html_escape($field) ?>" rows="4"><?= html_escape(set_value($field, '', false)) ?></textarea></div><?php } ?></div></details></section>
<div class="os-form-actions"><button type="submit" class="btn btn-primary" id="btnContinuar">Criar ordem de serviço</button><a class="btn" href="<?= site_url('os') ?>">Voltar à listagem</a></div>
</form></div>
