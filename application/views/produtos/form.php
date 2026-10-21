<?php
$this->load->helper('form');
$productEditing = $productEditing ?? false;
$result = $result ?? null;
$productValue = function ($field) use ($productEditing, $result) {
    return set_value($field, $productEditing ? (string) ($result->$field ?? '') : '');
};
?>
<section class="ui-page" aria-labelledby="product-title">
    <header class="ui-page-header"><div><p class="ui-eyebrow">CATÁLOGO</p><h1 id="product-title"><?= $productEditing ? 'Editar produto' : 'Cadastrar produto' ?></h1><p>Organize a identificação, os preços e o estoque do produto.</p></div><a class="ui-button" href="<?= site_url('produtos') ?>">Voltar aos produtos</a></header>
    <div class="ui-card">
        <?php if ($custom_error) { ?><div class="ui-notice ui-notice-error" role="alert"><?= $custom_error ?></div><?php } ?>
        <p class="ui-hint">Os campos com * são obrigatórios.</p>
        <?= form_open(current_url(), ['id' => 'formProduto']) ?>
        <?php if ($productEditing) {
            echo form_hidden('idProdutos', (int) $result->idProdutos);
        } ?>
        <fieldset class="ui-fieldset"><legend>Identificação</legend><div class="ui-form-grid">
            <div class="control-group ui-field-wide"><label for="descricao">Descrição *</label><div class="controls"><input id="descricao" name="descricao" type="text" required value="<?= $productValue('descricao') ?>" placeholder="Ex.: SSD SATA 480 GB"></div></div>
            <div class="control-group"><label for="codDeBarra">Código de barras</label><div class="controls"><input id="codDeBarra" name="codDeBarra" type="text" value="<?= $productValue('codDeBarra') ?>"></div></div>
            <div class="control-group"><label for="unidade">Unidade *</label><div class="controls"><select id="unidade" name="unidade" required data-selected="<?= $productValue('unidade') ?>"><option value="">Selecione a unidade</option></select></div></div>
        </div></fieldset>
        <fieldset class="ui-fieldset"><legend>Preços</legend><div class="ui-form-grid">
            <div class="control-group"><label for="precoCompra">Preço de compra (R$) *</label><div class="controls"><input id="precoCompra" class="money" data-affixes-stay="true" data-thousands="" data-decimal="." type="text" name="precoCompra" inputmode="decimal" required value="<?= $productValue('precoCompra') ?>"></div></div>
            <div class="control-group"><label for="precoVenda">Preço de venda (R$) *</label><div class="controls"><input id="precoVenda" class="money" data-affixes-stay="true" data-thousands="" data-decimal="." type="text" name="precoVenda" inputmode="decimal" required value="<?= $productValue('precoVenda') ?>"></div></div>
            <div class="control-group"><label for="selectLucro">Tipo de cálculo do lucro</label><div class="controls"><select id="selectLucro" name="selectLucro" aria-describedby="profit-help"><option value="markup">Markup sobre a compra</option><option value="margemLucro">Margem sobre a venda</option></select></div></div>
            <div class="control-group"><label for="Lucro">Percentual de lucro (%)</label><div class="controls"><input id="Lucro" name="Lucro" type="text" inputmode="decimal" maxlength="3" placeholder="Ex.: 30" aria-describedby="profit-help"></div></div>
        </div><p id="profit-help" class="ui-hint">Opcional: informe o percentual para calcular o preço de venda a partir do preço de compra.</p><p id="errorAlert" class="ui-field-error" role="alert" aria-live="polite"></p></fieldset>
        <fieldset class="ui-fieldset"><legend>Estoque e movimentação</legend><div class="ui-form-grid">
            <div class="control-group"><label for="estoque">Estoque atual *</label><div class="controls"><input id="estoque" name="estoque" type="text" inputmode="decimal" required value="<?= $productValue('estoque') ?>"></div></div>
            <div class="control-group"><label for="estoqueMinimo">Estoque mínimo</label><div class="controls"><input id="estoqueMinimo" name="estoqueMinimo" type="text" inputmode="decimal" value="<?= $productValue('estoqueMinimo') ?>"></div></div>
        </div><p class="ui-field-label">Movimentações permitidas</p><div class="ui-checkbox-row"><label for="entrada"><input type="checkbox" id="entrada" name="entrada" value="1" <?= set_checkbox('entrada', '1', ! $this->input->post() && (! $productEditing || (int) $result->entrada === 1)) ?>> Entrada</label><label for="saida"><input type="checkbox" id="saida" name="saida" value="1" <?= set_checkbox('saida', '1', ! $this->input->post() && (! $productEditing || (int) $result->saida === 1)) ?>> Saída</label></div></fieldset>
        <footer class="ui-form-actions"><button type="submit" class="ui-button ui-button-primary">Salvar produto</button><a class="ui-button" href="<?= site_url('produtos') ?>">Cancelar</a></footer>
        </form>
    </div>
</section>

<script src="<?php echo base_url() ?>assets/js/jquery.validate.js"></script>
<script src="<?php echo base_url(); ?>assets/js/maskmoney.js"></script>
<script type="text/javascript">
    function calcLucro(precoCompra, Lucro) {
        var lucroTipo = $('#selectLucro').val();
        var precoVenda;
        
        if (lucroTipo === 'markup') {
            precoVenda = (precoCompra * (1 + Lucro / 100)).toFixed(2);
        } else if (lucroTipo === 'margemLucro') {
            precoVenda = (precoCompra / (1 - (Lucro / 100))).toFixed(2);
        }
        
        return precoVenda;
    }
    
    function atualizarPrecoVenda() {
        var precoCompra = Number($("#precoCompra").val());
        var lucro = Number($("#Lucro").val());
        
        if (precoCompra > 0 && lucro >= 0) {
            $('#precoVenda').val(calcLucro(precoCompra, lucro));
        }
    }
    
    $("#precoCompra, #Lucro, #selectLucro").on('input change', atualizarPrecoVenda);

    $("#precoCompra, #Lucro").on('input change', function() {
        if ($("#precoCompra").val() == '0.00' && $('#precoVenda').val() != '') {
            $('#errorAlert').text('Você não pode preencher valor de compra e depois apagar.').show();
            $('#precoVenda').val('');
            $("#precoCompra").focus();
        } else if ($("#precoCompra").val() != '' && $("#Lucro").val() != '') {
            atualizarPrecoVenda();
        }
    });

    $("#Lucro").keyup(function() {
        this.value = this.value.replace(/[^0-9.]/g, '');
        if ($("#precoCompra").val() == null || $("#precoCompra").val() == '') {
            $('#errorAlert').text('Preencher valor da compra primeiro.').show();
            $('#Lucro').val('');
            $('#precoVenda').val('');
            $("#precoCompra").focus();

        } else if (Number($("#Lucro").val()) >= 0) {
            $('#precoVenda').val(calcLucro(Number($("#precoCompra").val()), Number($("#Lucro").val())));
        } else {
            $('#errorAlert').text('Não é permitido número negativo.').show();
            $('#Lucro').val('');
            $('#precoVenda').val('');
        }
    });

    $('#precoVenda').focusout(function () {
        if (Number($('#precoVenda').val()) < Number($("#precoCompra").val())) {
            $('#errorAlert').text('Preço de venda não pode ser menor que o preço de compra.').show();
            $('#precoVenda').val('');
        }
    });

    $(document).ready(function() {
        $(".money").maskMoney();
        $.getJSON('<?php echo base_url() ?>assets/json/tabela_medidas.json', function(data) {
            for (i in data.medidas) {
                $('#unidade').append(new Option(data.medidas[i].descricao, data.medidas[i].sigla));
                }
                if ($('#unidade').attr('data-selected')) {
                    $('#unidade').val($('#unidade').attr('data-selected'));
            }
        });
        $('#formProduto').validate({
            rules: {
                descricao: {
                    required: true
                },
                unidade: {
                    required: true
                },
                precoCompra: {
                    required: true
                },
                precoVenda: {
                    required: true
                },
                estoque: {
                    required: true
                }
            },
            messages: {
                descricao: {
                    required: 'Campo Requerido.'
                },
                unidade: {
                    required: 'Campo Requerido.'
                },
                precoCompra: {
                    required: 'Campo Requerido.'
                },
                precoVenda: {
                    required: 'Campo Requerido.'
                },
                estoque: {
                    required: 'Campo Requerido.'
                }
            },
            errorClass: "help-inline",
            errorElement: "span",
            highlight: function(element, errorClass, validClass) {
                $(element).parents('.control-group').addClass('error');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).parents('.control-group').removeClass('error');
                $(element).parents('.control-group').addClass('success');
            }
        });
    });
</script>
