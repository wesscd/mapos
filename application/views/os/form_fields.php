<?php
$editing = isset($result);
$osInput = function ($name, $fallback = '') {
    $posted = $this->input->post($name);
    return html_escape(is_string($posted) ? $posted : (string) ($fallback ?? ''));
};
$selectedStatus = $this->input->post('status');
$selectedStatus = is_string($selectedStatus) ? $selectedStatus : ($editing ? $result->status : 'Aberto');
?>
<div class="os-field-grid">
    <label for="cliente">Cliente *<input id="cliente" name="cliente" required autocomplete="off" data-os-catalog="Cliente" data-os-id="clientes_id" value="<?= $osInput('cliente', $editing ? $result->nomeCliente : '') ?>"></label>
    <input type="hidden" id="clientes_id" name="clientes_id" value="<?= $osInput('clientes_id', $editing ? $result->clientes_id : '') ?>">
    <label for="tecnico">Técnico / responsável *<input id="tecnico" name="tecnico" required autocomplete="off" data-os-catalog="Usuario" data-os-id="usuarios_id" value="<?= $osInput('tecnico', $editing ? $result->nome : $this->session->userdata('nome_admin')) ?>"></label>
    <input type="hidden" id="usuarios_id" name="usuarios_id" value="<?= $osInput('usuarios_id', $editing ? $result->usuarios_id : $this->session->userdata('id_admin')) ?>">
    <label for="status">Status *<select id="status" name="status" required><?php foreach (['Aberto', 'Orçamento', 'Negociação', 'Aprovado', 'Aguardando Peças', 'Em Andamento', 'Finalizado', 'Cancelado'] as $statusOption) { ?><option value="<?= html_escape($statusOption) ?>" <?= $selectedStatus === $statusOption ? 'selected' : '' ?>><?= html_escape($statusOption) ?></option><?php } ?><?php if ($selectedStatus === 'Faturado') { ?><option value="Faturado" selected>Faturado</option><?php } ?></select></label>
    <label for="dataInicial">Data inicial *<input id="dataInicial" name="dataInicial" class="datepicker" placeholder="dd/mm/aaaa" required autocomplete="off" value="<?= $osInput('dataInicial', $editing ? date('d/m/Y', strtotime($result->dataInicial)) : date('d/m/Y')) ?>"></label>
    <label for="dataFinal">Data final prevista *<input id="dataFinal" name="dataFinal" class="datepicker" placeholder="dd/mm/aaaa" required autocomplete="off" value="<?= $osInput('dataFinal', $editing ? date('d/m/Y', strtotime($result->dataFinal)) : date('d/m/Y')) ?>"></label>
    <label for="garantia">Garantia (dias)<input id="garantia" name="garantia" type="number" min="0" max="9999" value="<?= $osInput('garantia', $editing ? $result->garantia : '0') ?>"></label>
    <label for="termoGarantia">Termo de garantia<input id="termoGarantia" name="termoGarantia" autocomplete="off" data-os-catalog="TermoGarantia" data-os-id="garantias_id" value="<?= $osInput('termoGarantia', $editing ? $result->refGarantia : '') ?>"></label>
    <input type="hidden" id="garantias_id" name="garantias_id" value="<?= $osInput('garantias_id', $editing ? $result->garantias_id : '') ?>">
</div>
<p class="os-help">Selecione cliente, responsável e termo de garantia nas sugestões. O status da equipe e a aprovação do cliente são registros distintos.</p>
<div class="os-field-grid os-text-fields">
<?php foreach (['descricaoProduto' => 'Descrição do equipamento / atendimento', 'defeito' => 'Problema relatado pelo cliente'] as $name => $label) { ?>
    <div><label for="<?= html_escape($name) ?>"><?= html_escape($label) ?></label><textarea class="editor" id="<?= html_escape($name) ?>" name="<?= html_escape($name) ?>" rows="4"><?= $osInput($name, $editing ? $result->$name : '') ?></textarea></div>
<?php } ?>
</div>
