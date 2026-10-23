<link rel="stylesheet" href="<?= base_url('assets/css/checklists.css') ?>?v=1">
<section class="ui-page service-checklists">
    <header class="ui-page-header"><div><p class="ui-eyebrow">CONFIGURAÇÕES</p><h1>Modelos de checklist técnico</h1><p>Defina verificações e procedimentos por tipo de equipamento. Alterações valem somente para novas aplicações; cada OS preserva sua cópia.</p></div><a class="ui-button" href="<?= site_url('mapos/configurar') ?>">Voltar às configurações</a></header>
    <p class="ui-notice">Itens de execução não são serviços cobrados. “Exige autorização” pede a referência do escopo aprovado antes de registrar a intervenção como realizada. Cuidados complementares também precisam respeitar o combinado com o cliente.</p>
    <?php $draft = $this->session->flashdata('checklist_model_draft'); ?>
    <?php foreach ($models as $model) {
        $pending = ($draft['id'] ?? 0) === (int) $model->id ? $draft['dados'] : null;
        $values = $pending ?? (array) $model;
        $items = $pending['itens'] ?? json_decode($model->itens, true); ?>
        <details class="ui-card" <?= $pending ? 'open' : '' ?>><summary><?= html_escape(Checklist_service::TYPES[$model->tipo]) ?> · <?= html_escape($model->nome) ?> · versão <?= (int) $model->versao ?><?= $model->ativo ? '' : ' · desativado' ?></summary>
            <?php if ($pending) { ?><p role="alert">Rascunho preservado. Confira os dados; se houve alteração por outra pessoa, recarregue para consultar o modelo atual.</p><?php } ?>
            <?= form_open('checklists/modelo/' . (int) $model->id) ?>
                <input type="hidden" name="versao" value="<?= (int) ($values['versao'] ?? $model->versao) ?>">
                <div class="checklist-fields"><label>Nome do modelo<input name="nome" required maxlength="100" value="<?= html_escape($values['nome'] ?? $model->nome) ?>"></label><label><input type="checkbox" name="ativo" value="1" <?= ! empty($values['ativo']) ? 'checked' : '' ?>> Modelo ativo para novas aplicações</label></div>
                <?php foreach ($items as $index => $item) { ?>
                    <div class="checklist-model-item"><p><?= $item['fase'] === 'analise' ? 'Análise técnica' : 'Execução e conferência final' ?></p>
                        <input type="hidden" name="itens[<?= (int) $index ?>][fase]" value="<?= html_escape($item['fase']) ?>">
                        <div class="checklist-fields"><label>Descrição<input name="itens[<?= (int) $index ?>][descricao]" required maxlength="180" value="<?= html_escape($item['descricao']) ?>"></label>
                        <label>Tipo<select name="itens[<?= (int) $index ?>][escopo]">
                            <?php foreach (Checklist_service::SCOPES as $scope => $label) {
                                if ($item['fase'] === 'analise' && $scope !== 'verificacao') {
                                    continue;
                                } ?><option value="<?= $scope ?>" <?= $item['escopo'] === $scope ? 'selected' : '' ?>><?= html_escape($label) ?></option><?php } ?>
                        </select></label></div><label><input type="checkbox" name="itens[<?= (int) $index ?>][remover]" value="1" <?= ! empty($item['remover']) ? 'checked' : '' ?>> Retirar das próximas aplicações</label>
                    </div>
                <?php } ?>
                <div class="checklist-fields"><label>Novas verificações de análise (uma por linha)<textarea name="novos_analise" maxlength="10000" rows="4"><?= html_escape($values['novos_analise'] ?? '') ?></textarea></label><label>Novos procedimentos de execução (um por linha)<textarea name="novos_execucao" maxlength="10000" rows="4"><?= html_escape($values['novos_execucao'] ?? '') ?></textarea></label><label>Tipo dos novos procedimentos<select name="novo_escopo"><?php foreach (Checklist_service::SCOPES as $scope => $label) { ?><option value="<?= $scope ?>" <?= ($values['novo_escopo'] ?? 'autorizacao') === $scope ? 'selected' : '' ?>><?= html_escape($label) ?></option><?php } ?></select></label></div>
                <button class="ui-button ui-button-primary" type="submit">Salvar modelo de <?= html_escape(Checklist_service::TYPES[$model->tipo]) ?></button>
            </form>
        </details>
    <?php } ?>
</section>
