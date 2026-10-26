<link rel="stylesheet" href="<?= base_url('assets/css/checklists.css') ?>?v=1">
<?php $canWorkflow = $osCanMutate;
$checklistPhase = $checklistPhase ?? 'analise';
$checklistPane = $checklistPhase === 'execucao' ? 'execucao' : 'analise';
$draft = $this->session->flashdata('checklist_draft');
$draftInput = ($draft['os'] ?? 0) === (int) $result->idOs ? $draft['dados'] : [];
$photoPermission = $this->permission->checkPermission($this->session->userdata('permissao'), 'vCliente');
$actionUrl = 'checklists/salvar/' . (int) $result->idOs;
$returnTo = ! empty($osEditing) ? 'editar' : 'visualizar'; ?>
<section class="mapos-card service-checklists">
<div class="checklist-progress"><h3><?= $checklistPhase === 'execucao' ? 'Checklist de execução e testes' : 'Checklist de análise técnica' ?></h3><?php if ($checklistPhase === 'execucao') { ?><a class="btn" target="_blank" rel="noopener" href="<?= html_escape(site_url('checklists/resumo/' . (int) $result->idOs)) ?>">Resumo dos procedimentos realizados</a><?php } ?></div>
<p>Registre verificações e procedimentos por equipamento. O checklist não acrescenta serviços, produtos ou valores ao orçamento. Não informe senhas ou dados pessoais nas observações e fotos.</p>
<?php if ($draftInput) { ?><p role="alert" class="alert">Rascunho preservado para correção. Selecione novamente a foto antes de salvar.</p><?php } ?>
<?php if ($canWorkflow && $checklistPhase === 'analise') { ?>
<details><summary>Aplicar modelo por tipo de equipamento</summary><p>O modelo ativo é aplicado ao vincular um equipamento com tipo configurado à OS. Para atendimentos anteriores, aplique aqui. Reaplicar preserva a cópia existente.</p>
<?= form_open($actionUrl) ?><input type="hidden" name="retorno" value="<?= $returnTo ?>"><input type="hidden" name="aba" value="<?= $checklistPane ?>"><input type="hidden" name="acao" value="aplicar">
<div class="checklist-fields"><label>Equipamento<select id="checklist-equipment" name="equipamento_id"><option value="0">Checklist geral deste atendimento</option><?php foreach ($workflow['equipamentos'] as $equipment) { ?><option value="<?= (int) $equipment->idEquipamentos ?>" data-type="<?= html_escape($equipment->checklist_tipo ?? '') ?>">#<?= (int) $equipment->idEquipamentos ?> · <?= html_escape($equipment->equipamento) ?></option><?php } ?></select></label>
<label>Tipo do modelo<select id="checklist-type" name="tipo" required><option value="">Selecione o tipo</option><?php foreach (Checklist_service::TYPES as $type => $label) { ?><option value="<?= $type ?>"><?= html_escape($label) ?></option><?php } ?></select></label></div><button class="btn btn-primary" type="submit">Aplicar modelo à OS</button></form></details>
<?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema')) { ?><p><a href="<?= html_escape(site_url('checklists')) ?>">Configurar modelos de notebook, desktop e outros equipamentos</a></p><?php } ?>
<?php } ?>
<?php if (! $workflow['checklist_grupos']) { ?><p class="mapos-muted">Nenhum modelo aplicado. Escolha o tipo no cadastro do equipamento para os próximos vínculos ou aplique um modelo na aba Análise técnica.</p><?php } ?>
<?php foreach ($workflow['checklist_grupos'] as $group) { ?>
<section class="mapos-card"><h3><?= html_escape($group->equipamento_nome) ?></h3><p class="checklist-audit">Modelo <?= html_escape($group->modelo_nome) ?> · versão <?= (int) $group->modelo_versao ?> · aplicado em <?= html_escape($group->created_at) ?>. Cópia preservada neste atendimento.</p>
<?php foreach ([$checklistPhase => $checklistPhase === 'execucao' ? 'Execução e conferência final' : 'Análise técnica'] as $phase => $phaseLabel) {
    $phaseItems = array_filter($group->itens, fn ($item) => $item->fase === $phase);
    $answered = count(array_filter($phaseItems, fn ($item) => $item->resultado !== 'pendente')); ?>
<h4><?= $phaseLabel ?> · <?= $answered ?>/<?= count($phaseItems) ?> respondidos</h4>
<?php foreach ($phaseItems as $item) {
    $isDraft = ($draftInput['acao'] ?? '') === 'item' && (int) ($draftInput['item_id'] ?? 0) === (int) $item->id;
    $values = $isDraft ? $draftInput : (array) $item; ?>
<details <?= $isDraft ? 'open' : '' ?>><summary><?= html_escape($item->descricao) ?><span class="checklist-state"><?= html_escape(Checklist_service::RESULTS[$phase][$item->resultado]) ?></span></summary>
<p class="checklist-scope"><?= html_escape(Checklist_service::SCOPES[$item->escopo]) ?></p>
<p><a href="<?= html_escape(site_url('checklists/historico/' . (int) $item->id)) ?>">Consultar histórico deste item</a></p>
<?php if ($item->updated_at) { ?><p class="checklist-audit">Último registro: <?= html_escape($item->responsavel ?? 'Responsável não informado') ?> · <?= html_escape($item->updated_at) ?>. Alterações anteriores ficam no histórico da OS.</p><?php } ?>
<?php if ($canWorkflow) { ?>
<?= form_open_multipart($actionUrl) ?><input type="hidden" name="retorno" value="<?= $returnTo ?>"><input type="hidden" name="aba" value="<?= $checklistPane ?>"><input type="hidden" name="acao" value="item"><input type="hidden" name="item_id" value="<?= (int) $item->id ?>"><input type="hidden" name="versao" value="<?= (int) ($values['versao'] ?? $item->versao) ?>">
<div class="checklist-fields"><label>Resultado<select name="resultado"><?php foreach (Checklist_service::RESULTS[$phase] as $state => $label) { ?><option value="<?= $state ?>" <?= ($values['resultado'] ?? '') === $state ? 'selected' : '' ?>><?= html_escape($label) ?></option><?php } ?></select></label>
<label>Observação / justificativa<textarea name="observacoes" maxlength="2000" rows="3" placeholder="Obrigatória para Atenção e Não realizado"><?= html_escape($values['observacoes'] ?? '') ?></textarea></label>
<?php if ($item->escopo === 'autorizacao') { ?><label>Referência da autorização / escopo aprovado<input name="autorizacao" maxlength="500" value="<?= html_escape($values['autorizacao'] ?? '') ?>" placeholder="Ex.: orçamento aprovado, item e data"><span class="checklist-audit">Obrigatória ao marcar Realizado. Não registra aprovação financeira pelo cliente.</span></label><?php } ?>
<?php if ($photoPermission && count($item->fotos) < 3) { ?><label>Foto opcional (até três por item)<input type="file" name="checklist_foto" accept="image/jpeg,image/png"><span class="checklist-audit">JPEG/PNG até 8 MB. As fotos não são substituídas.</span></label><?php } ?></div>
<button class="btn btn-primary" type="submit">Salvar este item</button></form>
<?php } else { ?><p><?= nl2br(html_escape($item->observacoes ?? '')) ?></p><?php if ($item->autorizacao) { ?><p>Referência de autorização: <?= html_escape($item->autorizacao) ?></p><?php } ?><?php } ?>
<?php if ($photoPermission) {
    foreach ($item->fotos as $photo) { ?><a class="checklist-photo" href="<?= html_escape(site_url('checklists/foto/' . (int) $photo->id)) ?>" target="_blank" rel="noopener"><img loading="lazy" src="<?= html_escape(site_url('checklists/foto/' . (int) $photo->id)) ?>" alt="Foto de <?= html_escape($item->descricao) ?>"><span>Foto #<?= (int) $photo->id ?></span></a><?php }
    } ?>
</details>
<?php }
} ?>
<?php if ($canWorkflow) {
    $adding = ($draftInput['acao'] ?? '') === 'adicionar' && (int) ($draftInput['grupo_id'] ?? 0) === (int) $group->id ? $draftInput : []; ?>
<details <?= $adding ? 'open' : '' ?>><summary>Adicionar item específico desta OS</summary>
<?= form_open($actionUrl) ?><input type="hidden" name="retorno" value="<?= $returnTo ?>"><input type="hidden" name="aba" value="<?= $checklistPane ?>"><input type="hidden" name="acao" value="adicionar"><input type="hidden" name="grupo_id" value="<?= (int) $group->id ?>">
<div class="checklist-fields"><label>Descrição<input name="descricao" required maxlength="180" value="<?= html_escape($adding['descricao'] ?? '') ?>"></label><input type="hidden" name="fase" value="<?= $checklistPhase ?>"><label>Tipo<select name="escopo"><?php foreach (Checklist_service::SCOPES as $scope => $label) {
    if ($checklistPhase === 'analise' && $scope !== 'verificacao') {
        continue;
    } ?><option value="<?= $scope ?>" <?= ($adding['escopo'] ?? 'verificacao') === $scope ? 'selected' : '' ?>><?= html_escape($label) ?></option><?php } ?></select></label></div><button class="btn" type="submit">Adicionar item à OS</button></form></details>
<?php } ?></section>
<?php } ?>
<?php if ($checklistPhase === 'execucao' && $workflow['checklist']) { ?><details><summary>Checklist anterior · registros preservados</summary><?php foreach ($workflow['checklist'] as $legacy) { ?><p><?= $legacy->concluido ? '✓' : '○' ?> <?= html_escape($legacy->descricao) ?></p><?php } ?><p class="mapos-muted">Os itens antigos não foram convertidos automaticamente em resultados de análise ou execução.</p></details><?php } ?>
</section><?php if ($checklistPhase === 'analise') { ?><script src="<?= base_url('assets/js/checklists.js') ?>" defer></script><?php } ?>
