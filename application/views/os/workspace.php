<?php
$osMutable = $editavel && ! $result->entregue_em && ! (int) $result->faturado && ! in_array($result->status, ['Cancelado', 'Faturado'], true);
$osPermission = $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs');
$osCanMutate = $osMutable && $osPermission;
$osGross = round($totalProdutos + $totalServico, 2);
$osNet = $result->tipo_desconto ? (float) $result->valor_desconto : $osGross;
$this->load->view('os/workflow', compact('osEditing', 'osMutable', 'osPermission', 'osCanMutate', 'osGross', 'osNet'));
?>
<div class="os-workspace-v2" data-os-root data-os-base="<?= site_url('os') ?>" data-os-editing="<?= $osEditing ? '1' : '0' ?>">
    <div class="os-feedback" role="status" aria-live="polite" data-os-feedback hidden></div>
    <?php if (! empty($custom_error)) { ?><div class="alert alert-error" role="alert" data-os-form-errors><?= html_escape(strip_tags((string) $custom_error)) ?></div><?php } ?>
    <?php if ($osEditing) { ?><div class="os-draft-bar" data-os-draft hidden><strong>Há alterações não salvas no resumo ou na análise.</strong><button type="submit" class="btn btn-primary" form="formOs">Salvar alterações da OS</button><p>Salve antes de atualizar checklist, orçamento, vistoria ou anexos.</p></div><?php } ?>
    <section id="os-pane-resumo" class="os-pane mapos-card" data-os-panel="resumo" role="tabpanel" aria-labelledby="os-tab-resumo" tabindex="0">
        <?php if ($osEditing) { ?>
            <h2>Dados do atendimento</h2>
            <form id="formOs" action="<?= current_url() ?>" method="post" data-os-main>
                <input type="hidden" name="idOs" value="<?= (int) $result->idOs ?>">
                <?php $this->load->view('os/form_fields'); ?>
                <div class="os-form-actions"><button type="submit" class="btn btn-primary" id="btnContinuar">Salvar resumo e análise</button><a class="btn" href="<?= site_url('os') ?>">Voltar à listagem</a></div>
            </form>
        <?php } else { ?>
            <div class="os-section-heading"><h2>Resumo do atendimento</h2><?php if ($editavel && $osPermission) { ?><a class="btn btn-primary" href="<?= site_url('os/editar/' . (int) $result->idOs) ?>">Editar OS</a><?php } ?></div>
            <dl class="os-summary-grid"><div><dt>Cliente</dt><dd><?= html_escape($result->nomeCliente) ?></dd></div><div><dt>Responsável</dt><dd><?= html_escape($result->nome) ?></dd></div><div><dt>Data inicial</dt><dd><?= html_escape(date('d/m/Y', strtotime($result->dataInicial))) ?></dd></div><div><dt>Data final prevista</dt><dd><?= $result->dataFinal ? html_escape(date('d/m/Y', strtotime($result->dataFinal))) : 'Não informada' ?></dd></div><div><dt>Garantia</dt><dd><?= (int) $result->garantia ?> dias</dd></div><div><dt>Termo de garantia</dt><dd><?= html_escape($result->refGarantia ?: 'Não informado') ?></dd></div></dl>
            <?php foreach (['descricaoProduto' => 'Descrição do equipamento / atendimento', 'defeito' => 'Problema relatado pelo cliente'] as $field => $label) { ?><h3><?= html_escape($label) ?></h3><div class="os-prose"><?= nl2br(html_escape(trim(strip_tags((string) $result->$field)))) ?: 'Não informado.' ?></div><?php } ?>
        <?php } ?>
    </section>
    <section id="os-pane-equipamento" class="os-pane mapos-card" data-os-panel="equipamento" role="tabpanel" aria-labelledby="os-tab-equipamento" tabindex="0"><?php $this->load->view('equipamentos/os', ['equipmentPhase' => 'entrada']); ?></section>
    <section id="os-pane-analise" class="os-pane mapos-card" data-os-panel="analise" role="tabpanel" aria-labelledby="os-tab-analise" tabindex="0">
        <h2>Análise técnica</h2><div class="os-field-grid os-text-fields">
        <?php foreach (['laudoTecnico' => 'Diagnóstico técnico', 'observacoes' => 'Observações da execução'] as $field => $label) { ?><div><label for="<?= html_escape($field) ?>"><?= html_escape($label) ?></label><?php if ($osEditing) {
            $posted = $this->input->post($field); ?><textarea class="editor" form="formOs" id="<?= html_escape($field) ?>" name="<?= html_escape($field) ?>" rows="6"><?= html_escape(is_string($posted) ? $posted : (string) $result->$field) ?></textarea><?php } else { ?><div class="os-prose"><?= nl2br(html_escape(trim(strip_tags((string) $result->$field)))) ?: 'Não informado.' ?></div><?php } ?></div><?php } ?></div>
        <?php if ($osEditing) { ?><div class="os-form-actions"><button type="submit" class="btn btn-primary" form="formOs">Salvar análise e resumo</button></div><?php } ?>
        <?php $this->load->view('os/workflow_checklist'); ?>
    </section>
    <section id="os-pane-orcamento" class="os-pane mapos-card" data-os-panel="orcamento" role="tabpanel" aria-labelledby="os-tab-orcamento" tabindex="0">
        <h2>Orçamento</h2><?php $this->load->view('os/budget_items');
$this->load->view('os/workflow_budget');
$this->load->view('os/finance'); ?>
    </section>
    <section id="os-pane-entrega" class="os-pane mapos-card" data-os-panel="entrega" role="tabpanel" aria-labelledby="os-tab-entrega" tabindex="0"><?php $this->load->view('os/workflow_delivery'); ?></section>
    <section id="os-pane-historico" class="os-pane mapos-card" data-os-panel="historico" role="tabpanel" aria-labelledby="os-tab-historico" tabindex="0"><?php $this->load->view('os/workflow_history');
$this->load->view('os/records'); ?></section>
</div>
