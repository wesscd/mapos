<link rel="stylesheet" href="<?php echo base_url(); ?>assets/js/jquery-ui/css/smoothness/jquery-ui-1.9.2.custom.css" />
<script type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.js"></script>
<script type="text/javascript" src="<?php echo base_url() ?>assets/js/jquery.validate.js"></script>
<link rel="stylesheet" href="<?php echo base_url() ?>assets/trumbowyg/ui/trumbowyg.css">
<script type="text/javascript" src="<?php echo base_url() ?>assets/trumbowyg/trumbowyg.js"></script>
<script type="text/javascript" src="<?php echo base_url() ?>assets/trumbowyg/langs/pt_br.js"></script>
<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/custom.css" />

<div class="row-fluid" style="margin-top:0">
  <div class="span12">
    <div class="widget-box">
      <div class="widget-title">
        <h5>Cadastro de OS</h5>
      </div>
      <div class="widget-content nopadding tab-content">
        <div class="span12" id="divProdutosServicos" style=" margin-left: 0">
          <ul class="nav nav-tabs">
            <li class="active" id="tabDetalhes"><a href="#tab1" data-toggle="tab">Detalhes da OS</a></li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane active" id="tab1">
              <div class="span12" id="divCadastrarOs">
                <?php if ($custom_error == true) { ?>
                <div class="span12 alert alert-danger" id="divInfo" style="padding: 1%;">Dados incompletos, verifique os campos com asterisco ou se selecionou corretamente cliente, responsável e garantia.<br />
                  Ou se tem um cliente e um termo de garantia cadastrado.</div>
                <?php
} ?>
                <form action="<?php echo current_url(); ?>" method="post" id="formOs">
                  <div class="span12" style="padding: 1%">
                    <div class="span6">
                      <label for="cliente">Cliente<span class="required">*</span></label>
                      <input id="cliente" class="span12" type="text" name="cliente" value="" />
                      <input id="clientes_id" class="span12" type="hidden" name="clientes_id" value="" />
                    </div>
                    <div class="span6">
                      <label for="tecnico">Técnico / Responsável<span class="required">*</span></label>
                      <input id="tecnico" class="span12" type="text" name="tecnico" value="<?= $this->session->userdata('nome_admin'); ?>" />
                      <input id="usuarios_id" class="span12" type="hidden" name="usuarios_id" value="<?= $this->session->userdata('id_admin'); ?>" />
                    </div>
                  </div>
                  <div class="span12" style="padding: 1%; margin-left: 0">
                    <div class="span3">
                      <label for="status">Status<span class="required">*</span></label>
                      <select class="span12" name="status" id="status" value="">
                        <option value="Aberto">Aberto</option>
                        <option value="Orçamento">Orçamento</option>
                        <option value="Negociação">Negociação</option>
                        <option value="Aprovado">Aprovado</option>
                        <option value="Aguardando Peças">Aguardando Peças</option>
                        <option value="Em Andamento">Em Andamento</option>
                        <option value="Finalizado">Finalizado</option>
                        <option value="Faturado">Faturado</option>
                        <option value="Cancelado">Cancelado</option>
                      </select>
                    </div>
                    <div class="span3">
                      <label for="dataInicial">Data Inicial<span class="required">*</span></label>
                      <input id="dataInicial" autocomplete="off" class="span12 datepicker" type="text" name="dataInicial" value="<?php echo date('d/m/Y'); ?>" />
                    </div>
                    <div class="span3">
                      <label for="dataFinal">Data Final<span class="required">*</span></label>
                      <input id="dataFinal" autocomplete="off" class="span12 datepicker" type="text" name="dataFinal" value="" />
                    </div>
                    <div class="span3">
                      <label for="garantia">Garantia (dias)</label>
                      <input id="garantia" type="number" placeholder="Status s/g inserir nº/0" min="0" max="9999" class="span12" name="garantia" value="" />
                      <?php echo form_error('garantia'); ?>
                      <label for="termoGarantia">Termo Garantia</label>
                      <input id="termoGarantia" class="span12" type="text" name="termoGarantia" value="" />
                      <input id="garantias_id" class="span12" type="hidden" name="garantias_id" value="" />
                    </div>
                  </div>
                  <div class="span6" style="padding: 1%; margin-left: 0">
                    <label for="descricaoProduto">
                    <h4>Descrição Produto/Serviço</h4>
                    </label>
                    <textarea class="span12 editor" name="descricaoProduto" id="descricaoProduto" cols="30" rows="5"></textarea>
                  </div>
                  <div class="span6" style="padding: 1%; margin-left: 0">
                    <label for="defeito">
                    <h4>Defeito</h4>
                    </label>
                    <textarea class="span12 editor" name="defeito" id="defeito" cols="30" rows="5"></textarea>
                  </div>
                  <div class="span6" style="padding: 1%; margin-left: 0">
                    <label for="observacoes">
                    <h4>Observações</h4>
                    </label>
                    <textarea class="span12 editor" name="observacoes" id="observacoes" cols="30" rows="5"></textarea>
                  </div>
                  <div class="span6" style="padding: 1%; margin-left: 0">
                    <label for="laudoTecnico">
                    <h4>Laudo Técnico</h4>
                    </label>
                    <textarea class="span12 editor" name="laudoTecnico" id="laudoTecnico" cols="30" rows="5"></textarea>
                  </div>
                  <div class="span12" style="padding: 1%; margin-left: 0">
    <h4>Checklist de Recebimento do Equipamento</h4>
    <div class="span12" style="padding: 1%; margin-left: 0">
        <div class="span4">
            <label for="tipo_equipamento">Tipo de Equipamento<span class="required">*</span></label>
            <select id="tipo_equipamento" name="tipo_equipamento" class="span12">
                <option value="">Selecione...</option>
                <option value="Notebook">Notebook</option>
                <option value="Desktop">Desktop</option>
            </select>
        </div>
        <div class="span4">
            <label for="marca_modelo">Marca/Modelo<span class="required">*</span></label>
            <input id="marca_modelo" class="span12" type="text" name="marca_modelo" value="" />
        </div>
        <div class="span4">
            <label for="sn">S/N<span class="required">*</span></label>
            <input id="sn" class="span12" type="text" name="sn" style="text-transform: uppercase;" value="" />
        </div>
    </div>
    <div class="span12" style="padding: 1%; margin-left: 0">
        <div class="span4">
            <label for="pn">P/N</label>
            <input id="pn" class="span12" type="text" name="pn" style="text-transform: uppercase;" value="" />
        </div>
        <div class="span4">
            <label for="service_tag">Service Tag</label>
            <input id="service_tag" class="span12" type="text" name="service_tag" style="text-transform: uppercase;" value="" />
        </div>
        <div class="span4">
            <label for="fonte_alimentacao">Fonte de Alimentação</label>
            <select name="fonte_alimentacao" class="span12">
                <option value="OK">OK</option>
                <option value="Danificado">Danificado</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
    </div>

    <!-- Informações da Bateria -->
    <div id="bateria_info" class="span12" style="padding: 1%; margin-left: 0; display: none;">
        <h5>Informações da Bateria</h5>
        <div class="span6">
            <label for="bateria_pn">P/N da Bateria</label>
            <input id="bateria_pn" class="span12" type="text" name="bateria_pn" style="text-transform: uppercase;" value="" />
        </div>
        <div class="span6">
            <label for="bateria_sn">S/N da Bateria</label>
            <input id="bateria_sn" class="span12" type="text" name="bateria_sn" style="text-transform: uppercase;" value="" />
        </div>
    </div>

    <!-- Conexões -->
    <div class="span12" style="padding: 1%; margin-left: 0">
        <h5>Conexões - Portas Periféricas</h5>
        <div class="span3">
            <label>VGA</label>
            <select name="vga" class="span12">
                <option value="OK">OK</option>
                <option value="Danificado">Danificado</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
        <div class="span3">
            <label>HDMI</label>
            <select name="hdmi" class="span12">
                <option value="OK">OK</option>
                <option value="Danificado">Danificado</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
        <div class="span3">
            <label>USB</label>
            <select name="usb" class="span12">
                <option value="OK">OK</option>
                <option value="Danificado">Danificado</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
        <div class="span3">
            <label>DisplayPort</label>
            <select name="displayport" class="span12">
                <option value="OK">OK</option>
                <option value="Danificado">Danificado</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
    </div>

    <!-- Estado do Equipamento - Notebook -->
    <div id="estado_notebook" class="span12" style="padding: 1%; margin-left: 0; display: none;">
        <h5>Estado do Equipamento (Notebook)</h5>
        <div class="span3">
            <label>Tela</label>
            <select name="tela" class="span12">
                <option value="OK">OK</option>
                <option value="Falha">Falha</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
        <div class="span3">
            <label>Riscado</label>
            <select name="riscado" class="span12">
                <option value="OK">OK</option>
                <option value="Falha">Falha</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
        <div class="span3">
            <label>Teclado</label>
            <select name="teclado" class="span12">
                <option value="OK">OK</option>
                <option value="Falha">Falha</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
        <div class="span3">
            <label>Touchpad</label>
            <select name="touchpad" class="span12">
                <option value="OK">OK</option>
                <option value="Falha">Falha</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
        <div class="span3">
            <label>Webcam</label>
            <select name="webcam" class="span12">
                <option value="OK">OK</option>
                <option value="Falha">Falha</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
        <div class="span3">
            <label>Microfone</label>
            <select name="microfone" class="span12">
                <option value="OK">OK</option>
                <option value="Falha">Falha</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
        <div class="span3">
            <label>Carcaça OK</label>
            <select name="carcaca_ok" class="span12">
                <option value="OK">OK</option>
                <option value="Falha">Falha</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
        <div class="span3">
            <label>Tampa OK</label>
            <select name="tampa_ok" class="span12">
                <option value="OK">OK</option>
                <option value="Falha">Falha</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
    </div>

    <!-- Estado do Equipamento - Desktop -->
    <div id="estado_desktop" class="span12" style="padding: 1%; margin-left: 0; display: none;">
        <h5>Estado do Equipamento (Desktop)</h5>
        <div class="span3">
            <label>Gabinete</label>
            <select name="gabinete" class="span12">
                <option value="OK">OK</option>
                <option value="Falha">Falha</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
        <div class="span3">
            <label>Riscado</label>
            <select name="riscado" class="span12">
                <option value="OK">OK</option>
                <option value="Falha">Falha</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
        <div class="span3">
            <label>Carcaça OK</label>
            <select name="carcaca_ok" class="span12">
                <option value="OK">OK</option>
                <option value="Falha">Falha</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
    </div>

    <!-- Dobráveis e Acabamento - Notebook -->
    <div id="dobraveis_notebook" class="span12" style="padding: 1%; margin-left: 0; display: none;">
        <h5>Dobráveis e Acabamento (Notebook)</h5>
        <div class="span3">
            <label>Dobradiças OK</label>
            <select name="dobradicas_ok" class="span12">
                <option value="OK">OK</option>
                <option value="Falha">Falha</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
        <div class="span3">
            <label>Acabamento</label>
            <select name="acabamento" class="span12">
                <option value="OK">OK</option>
                <option value="Falha">Falha</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
        <div class="span3">
            <label>Riscado/Manchado</label>
            <select name="riscado_manchado" class="span12">
                <option value="OK">OK</option>
                <option value="Falha">Falha</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
    </div>

    <!-- Borrachas de Apoio e Parafusos da Carcaça -->
    <div class="span12" style="padding: 1%; margin-left: 0">
        <h5>Borrachas de Apoio e Parafusos da Carcaça</h5>
        <div class="span6">
            <label>Borrachas de Apoio</label>
            <select name="borrachas_apoio" class="span12">
                <option value="Completos">Completos</option>
                <option value="Faltam">Faltam</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
        <div class="span6">
            <label>Parafusos da Carcaça</label>
            <select name="parafusos_carcaca" class="span12">
                <option value="Completos">Completos</option>
                <option value="Faltam">Faltam</option>
                <option value="Não tem">Não tem</option>
            </select>
        </div>
    </div>

    <!-- Informações Complementares -->
    <div class="span12" style="padding: 1%; margin-left: 0">
        <label for="informacoes_complementares">Informações Complementares</label>
        <textarea class="span12" name="informacoes_complementares" id="informacoes_complementares" cols="30" rows="5"></textarea>
    </div>

    <!-- Imagens do Equipamento - Notebook -->
    <div id="imagens_notebook" class="span12" style="padding: 1%; margin-left: 0; display: none;">
        <h5>Imagens do Equipamento (Notebook)</h5>
        <div class="span4">
            <img src="<?php echo base_url(); ?>assets/img/notebook_lateral.jpg" alt="Notebook Lateral" style="width: 100%;">
        </div>
        <div class="span4">
            <img src="<?php echo base_url(); ?>assets/img/notebook_frente.jpg" alt="Notebook Frente" style="width: 100%;">
        </div>
        <div class="span4">
            <img src="<?php echo base_url(); ?>assets/img/notebook_fundo.jpg" alt="Notebook Fundo" style="width: 100%;">
        </div>
    </div>

    <!-- Imagens do Equipamento - Desktop -->
    <div id="imagens_desktop" class="span12" style="padding: 1%; margin-left: 0; display: none;">
        <h5>Imagens do Equipamento (Desktop)</h5>
        <div class="span4">
            <img src="<?php echo base_url(); ?>assets/img/desktop_lateral.jpg" alt="Desktop Lateral" style="width: 100%;">
        </div>
        <div class="span4">
            <img src="<?php echo base_url(); ?>assets/img/desktop_frente.jpg" alt="Desktop Frente" style="width: 100%;">
        </div>
        <div class="span4">
            <img src="<?php echo base_url(); ?>assets/img/desktop_traseira.jpg" alt="Desktop Traseira" style="width: 100%;">
        </div>
    </div>
</div>

<!-- Botões de Ação -->
<div class="form-actions">
    <div class="span12">
        <div class="span6 offset3">
            <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> Adicionar</button>
            <a href="<?php echo base_url(); ?>index.php/os" class="btn btn-default"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>
    </div>
</div>
                </form>
                
              </div>

            </div>
          </div>
        </div>
        . </div>
    </div>
  </div>
</div>
<script type="text/javascript">
$(document).ready(function() {
$("#cliente").autocomplete({
source: "<?php echo base_url(); ?>index.php/os/autoCompleteCliente",
minLength: 1,
select: function(event, ui) {
$("#clientes_id").val(ui.item.id);
}
});
$("#tecnico").autocomplete({
source: "<?php echo base_url(); ?>index.php/os/autoCompleteUsuario",
minLength: 1,
select: function(event, ui) {
$("#usuarios_id").val(ui.item.id);
}
});
$("#termoGarantia").autocomplete({
source: "<?php echo base_url(); ?>index.php/os/autoCompleteTermoGarantia",
minLength: 1,
select: function(event, ui) {
$("#garantias_id").val(ui.item.id);
}
});

$("#formOs").validate({
rules: {
cliente: {
required: true
},
tecnico: {
required: true
},
dataInicial: {
required: true
},
dataFinal: {
required: true
}

},
messages: {
cliente: {
required: 'Campo Requerido.'
},
tecnico: {
required: 'Campo Requerido.'
},
dataInicial: {
required: 'Campo Requerido.'
},
dataFinal: {
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
$(".datepicker").datepicker({
dateFormat: 'dd/mm/yy'
});
$('.editor').trumbowyg({
lang: 'pt_br',
semantic: { 'strikethrough': 's', }
});
});

// Manipulação do tipo de equipamento
$("#tipo_equipamento").change(function() {
    var tipo = $(this).val();
    if (tipo === "Notebook") {
        $("#estado_notebook").show();
        $("#estado_desktop").hide();
        $("#bateria_info").show();
        $("#dobraveis_notebook").show();
        $("#imagens_notebook").show();
        $("#imagens_desktop").hide();
    } else if (tipo === "Desktop") {
        $("#estado_notebook").hide();
        $("#estado_desktop").show();
        $("#bateria_info").hide();
        $("#dobraveis_notebook").hide();
        $("#imagens_notebook").hide();
        $("#imagens_desktop").show();
    } else {
        $("#estado_notebook").hide();
        $("#estado_desktop").hide();
        $("#bateria_info").hide();
        $("#dobraveis_notebook").hide();
        $("#imagens_notebook").hide();
        $("#imagens_desktop").hide();
    }
});
</script> 
