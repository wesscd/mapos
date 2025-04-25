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
                <div class="span12" id="divProdutosServicos" style="margin-left: 0">
                    <ul class="nav nav-tabs">
                        <li class="active" id="tabDetalhes"><a href="#tab1" data-toggle="tab">Detalhes da OS</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane active" id="tab1">
                            <div class="span12" id="divCadastrarOs">
                                <?php if (!empty($custom_error) && $custom_error !== false) { ?>
                                    <div class="span12 alert alert-danger" id="divInfo" style="padding: 1%;">
                                        <?php echo $custom_error; ?>
                                    </div>
                                <?php } elseif ($this->form_validation->run('os') === false) { ?>
                                    <div class="span12 alert alert-danger" id="divInfo" style="padding: 1%;">
                                        Dados incompletos, verifique os campos com asterisco ou se selecionou corretamente cliente, responsável e garantia.<br />
                                        Ou se tem um cliente e um termo de garantia cadastrado.
                                    </div>
                                <?php } ?>

                                <form action="<?php echo current_url(); ?>" method="post" id="formOs" enctype="multipart/form-data">
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

                                    <!-- Checklist de Recebimento do Equipamento -->
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
                                            <textarea class="span12 editor" name="informacoes_complementares" id="informacoes_complementares" cols="30" rows="5"></textarea>
                                        </div>

                                        <!-- Imagens do Equipamento - Notebook -->
                                        <div id="imagens_notebook" class="span12" style="padding: 1%; margin-left: 0; display: none;">
                                            <h5>Imagens do Equipamento (Notebook)</h5>
                                            <div style="display: flex; flex-direction: row; align-items: center; gap: 10px; flex-wrap: nowrap;">
                                                <div>
                                                    <img src="<?php echo base_url(); ?>assets/img/notebook_lateral.jpg" alt="Notebook Lateral" width="100" height="50">
                                                </div>
                                                <div>
                                                    <img src="<?php echo base_url(); ?>assets/img/notebook_frente.jpg" alt="Notebook Frente" width="100" height="50">
                                                </div>
                                                <div>
                                                    <img src="<?php echo base_url(); ?>assets/img/notebook_fundo.jpg" alt="Notebook Fundo" width="100" height="50">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Imagens do Equipamento - Desktop -->
                                        <div id="imagens_desktop" class="span12" style="padding: 1%; margin-left: 0; display: none;">
                                            <h5>Imagens do Equipamento (Desktop)</h5>
                                            <div style="display: flex; flex-direction: row; align-items: center; gap: 10px; flex-wrap: nowrap;">
                                                <div>
                                                    <img src="<?php echo base_url(); ?>assets/img/desktop_lateral.jpg" alt="Desktop Lateral" width="95" height="50">
                                                </div>
                                                <div>
                                                    <img src="<?php echo base_url(); ?>assets/img/desktop_frente.jpg" alt="Desktop Frente" width="70" height="50">
                                                </div>
                                                <div>
                                                    <img src="<?php echo base_url(); ?>assets/img/desktop_traseira.jpg" alt="Desktop Traseira" width="70" height="50">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Upload de Fotos -->
                                        <div class="span12" style="padding: 1%; margin-left: 0">
                                            <h5>Fotos de Inconsistências Físicas (Opcional)</h5>
                                            <div class="span12">
                                                <label for="fotos_inconsistencias">Selecione as fotos (máximo 5 imagens, formatos: JPG, PNG):</label>
                                                <input type="file" id="fotos_inconsistencias" name="fotos_inconsistencias[]" multiple accept="image/jpeg,image/png" class="span12" />
                                                <small class="text-muted">Você pode selecionar até 5 imagens. Tamanho máximo por imagem: 5MB.</small>
                                            </div>
                                            <div class="span12" id="preview_fotos" style="margin-top: 10px;">
                                                <!-- Aqui serão exibidas as pré-visualizações das imagens -->
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Botões de Ação -->
                                    <div class="span12" style="padding: 1%; margin-left: 0">
                                        <div class="span6 offset3" style="display:flex">
                                            <button class="button btn btn-success" id="btnContinuar">
                                                <span class="button__icon"><i class='bx bx-chevrons-right'></i></span><span class="button__text2">Continuar</span>
                                            </button>
                                            <a href="<?php echo base_url() ?>index.php/os" class="button btn btn-mini btn-warning" style="max-width: 160px">
                                                <span class="button__icon"><i class="bx bx-undo"></i></span><span class="button__text2">Voltar</span>
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Configuração do Trumbowyg
    $('.editor').trumbowyg({
        lang: 'pt_br',
        btns: [
            ['viewHTML'],
            ['undo', 'redo'],
            ['formatting'],
            ['strong', 'em', 'del'],
            ['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'],
            ['unorderedList', 'orderedList'],
            ['horizontalRule'],
            ['removeformat'],
            ['fullscreen']
        ],
        semantic: { 'strikethrough': 's' }
    });

    // Configuração dos Datepickers
    $(".datepicker").datepicker({
        dateFormat: 'dd/mm/yy',
        dayNames: ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'],
        dayNamesMin: ['D', 'S', 'T', 'Q', 'Q', 'S', 'S', 'D'],
        dayNamesShort: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
        monthNames: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
        monthNamesShort: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
        nextText: 'Próximo',
        prevText: 'Anterior'
    });

    // Autocomplete para Cliente
    $("#cliente").autocomplete({
        source: "<?php echo base_url(); ?>index.php/os/autoCompleteCliente",
        minLength: 1,
        select: function(event, ui) {
            $("#clientes_id").val(ui.item.id);
            $("#cliente").val(ui.item.label);
            console.log("Cliente selecionado - ID:", ui.item.id, "Label:", ui.item.label);
        }
    });

    // Autocomplete para Técnico
    $("#tecnico").autocomplete({
        source: "<?php echo base_url(); ?>index.php/os/autoCompleteUsuario",
        minLength: 1,
        select: function(event, ui) {
            $("#usuarios_id").val(ui.item.id);
            $("#tecnico").val(ui.item.label);
            console.log("Técnico selecionado - ID:", ui.item.id, "Label:", ui.item.label);
        }
    });

    // Autocomplete para Termo de Garantia
    $("#termoGarantia").autocomplete({
        source: "<?php echo base_url(); ?>index.php/os/autoCompleteTermoGarantia",
        minLength: 1,
        select: function(event, ui) {
            $("#garantias_id").val(ui.item.id);
            console.log("Termo Garantia selecionado - ID:", ui.item.id);
        }
    });

    // Validação do formulário (simplificada para testes)
    $("#formOs").validate({
        ignore: "#fotos_inconsistencias", // Ignora o campo de upload
        rules: {
            cliente: { required: false }, // Temporariamente desativado para testes testes realizados com sucesso!!!
            clientes_id: { required: false },
            tecnico: { required: false },
            usuarios_id: { required: false },
            dataInicial: { required: true },
            dataFinal: { required: true },
            status: { required: true },
            tipo_equipamento: { required: true },
            marca_modelo: { required: true },
            sn: { required: true }
        },
        messages: {
            cliente: { required: "Por favor, selecione um cliente." },
            clientes_id: { required: "Por favor, selecione um cliente." },
            tecnico: { required: "Por favor, selecione um técnico." },
            usuarios_id: { required: "Por favor, selecione um técnico." },
            dataInicial: { required: "Por favor, insira a data inicial." },
            dataFinal: { required: "Por favor, insira a data final." },
            status: { required: "Por favor, selecione o status." },
            tipo_equipamento: { required: "Por favor, informe o tipo de equipamento." },
            marca_modelo: { required: "Por favor, informe a marca/modelo." },
            sn: { required: "Por favor, informe o número de série." }
        },
        errorClass: "help-inline",
        errorElement: "span",
        highlight: function(element, errorClass, validClass) {
            $(element).parents('.control-group').addClass('error');
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).parents('.control-group').removeClass('error');
            $(element).parents('.control-group').addClass('success');
        },
        submitHandler: function(form) {
            console.log("Formulário validado com sucesso. Enviando...");
            console.log("Dados do formulário:", $(form).serialize());
            console.log("Arquivos selecionados:", $("#fotos_inconsistencias")[0].files);
            form.submit();
        },
        invalidHandler: function(event, validator) {
            console.log("Validação falhou. Erros:", validator.errorList);
        }
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

    // Pré-visualização de imagens
    $("#fotos_inconsistencias").change(function() {
        var files = this.files;
        var maxFiles = 5;
        var maxFileSize = 5 * 1024 * 1024; // 5MB em bytes
        var allowedTypes = ['image/jpeg', 'image/png'];
        var preview = $("#preview_fotos");
        preview.empty();

        console.log("Arquivos selecionados:", files);

        if (files.length > maxFiles) {
            alert("Você pode enviar no máximo 5 imagens.");
            return;
        }

        for (var i = 0; i < files.length; i++) {
            var file = files[i];
            if (!allowedTypes.includes(file.type)) {
                alert("Apenas arquivos JPG e PNG são permitidos: " + file.name);
                return;
            }
            if (file.size > maxFileSize) {
                alert("O arquivo " + file.name + " excede o tamanho máximo de 5MB.");
                return;
            }
            var reader = new FileReader();
            reader.onload = (function(f) {
                return function(e) {
                    var img = $("<img>").attr("src", e.target.result).css({
                        "width": "150px",
                        "height": "auto",
                        "margin": "5px"
                    });
                    preview.append(img);
                };
            })(file);
            reader.readAsDataURL(file);
        }
    });

    // Debug do envio do formulário
    $("#formOs").on("submit", function(e) {
        console.log("Evento de submit disparado.");
        if (!$(this).valid()) {
            console.log("Formulário inválido, envio bloqueado pelo jQuery Validate.");
            return false;
        }
        console.log("Formulário válido, prosseguindo com o envio.");
    });
});
</script>