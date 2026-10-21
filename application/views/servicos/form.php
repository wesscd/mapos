<?php
$serviceEditing = $serviceEditing ?? false;
$result = $result ?? null;
$serviceValue = function ($field) use ($serviceEditing, $result) {
    return set_value($field, $serviceEditing ? html_entity_decode((string) ($result->$field ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8') : '');
};
?>
<section class="ui-page" aria-labelledby="service-title"><header class="ui-page-header"><div><p class="ui-eyebrow">CATÁLOGO</p><h1 id="service-title"><?= $serviceEditing ? 'Editar serviço' : 'Cadastrar serviço' ?></h1><p>Defina o nome, o preço e a descrição do serviço oferecido.</p></div><a class="ui-button" href="<?= site_url('servicos') ?>">Voltar aos serviços</a></header>
<div class="ui-card"><?php if ($custom_error) { ?><div class="ui-notice ui-notice-error" role="alert"><?= $custom_error ?></div><?php } ?><p class="ui-hint">Os campos com * são obrigatórios.</p>
<?= form_open(current_url(), ['id' => 'formServico']) ?><?php if ($serviceEditing) {
    echo form_hidden('idServicos', (int) $result->idServicos);
} ?>
<div class="ui-form-grid"><div class="control-group"><label for="nome">Nome do serviço *</label><div class="controls"><input id="nome" name="nome" type="text" maxlength="45" required value="<?= $serviceValue('nome') ?>" placeholder="Ex.: Limpeza preventiva de notebook"></div></div><div class="control-group"><label for="preco">Preço (R$) *</label><div class="controls"><input id="preco" name="preco" type="text" class="money" inputmode="decimal" required data-affixes-stay="true" data-thousands="" data-decimal="." value="<?= $serviceValue('preco') ?>"></div></div><div class="control-group ui-field-wide"><label for="descricao">Descrição</label><div class="controls"><textarea id="descricao" name="descricao" rows="3" maxlength="45" aria-describedby="service-description-help" placeholder="Descreva o que está incluído neste serviço."><?= $serviceValue('descricao') ?></textarea><p id="service-description-help" class="ui-hint">Até 45 caracteres.</p></div></div></div>
<footer class="ui-form-actions"><button type="submit" class="ui-button ui-button-primary"><?= $serviceEditing ? 'Salvar alterações' : 'Salvar serviço' ?></button><a class="ui-button" href="<?= site_url('servicos') ?>">Cancelar</a></footer></form></div></section>
<script src="<?= base_url('assets/js/jquery.validate.js') ?>"></script><script src="<?= base_url('assets/js/maskmoney.js') ?>"></script>
<script>
$(function () {
    $('#formServico .money').maskMoney();
    $('#formServico').validate({rules:{nome:{required:true},preco:{required:true}},messages:{nome:{required:'Informe o nome do serviço.'},preco:{required:'Informe o preço do serviço.'}},errorClass:'help-inline',errorElement:'span',highlight:function(element){$(element).closest('.control-group').addClass('error');},unhighlight:function(element){$(element).closest('.control-group').removeClass('error');}});
});
</script>
