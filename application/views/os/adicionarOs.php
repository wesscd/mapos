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
                                <?php if (! empty($order_error)) { ?>
                                    <div class="span12 alert alert-danger" role="alert"><?= html_escape($order_error) ?></div>
                                <?php } elseif ($custom_error == true) { ?>
                                    <div class="span12 alert alert-danger" id="divInfo" style="padding: 1%;">Dados incompletos, verifique os campos com asterisco ou se selecionou corretamente cliente, responsável e garantia.<br />Ou se tem um cliente e um termo de garantia cadastrado.</div>
                                <?php
                                } ?>
                                <form action="<?php echo current_url(); ?>" method="post" id="formOs" enctype="multipart/form-data">
                                    <div class="span12" style="padding: 1%">
                                        <div class="span6">
                                            <label for="cliente">Cliente<span class="required">*</span></label>
                                            <input id="cliente" class="span12" type="text" name="cliente" value="<?= set_value('cliente') ?>" />
                                            <input id="clientes_id" class="span12" type="hidden" name="clientes_id" value="<?= set_value('clientes_id') ?>" />
                                        </div>
                                        <div class="span6">
                                            <label for="tecnico">Técnico / Responsável<span class="required">*</span></label>
                                            <input id="tecnico" class="span12" type="text" name="tecnico" value="<?= set_value('tecnico', $this->session->userdata('nome_admin')) ?>" />
                                            <input id="usuarios_id" class="span12" type="hidden" name="usuarios_id" value="<?= set_value('usuarios_id', $this->session->userdata('id_admin')) ?>" />
                                        </div>
                                    </div>
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
                                    <div class="span12" style="padding: 1%; margin-left: 0">
                                        <div class="span3">
                                            <label for="status">Status<span class="required">*</span></label>
                                            <select class="span12" name="status" id="status" value="">
                                                <option value="Aberto" <?= set_select('status', 'Aberto') ?>>Aberto</option>
                                                <option value="Orçamento" <?= set_select('status', 'Orçamento') ?>>Orçamento</option>
                                                <option value="Negociação" <?= set_select('status', 'Negociação') ?>>Negociação</option>
                                                <option value="Aprovado" <?= set_select('status', 'Aprovado') ?>>Aprovado</option>
                                                <option value="Aguardando Peças" <?= set_select('status', 'Aguardando Peças') ?>>Aguardando Peças</option>
                                                <option value="Em Andamento" <?= set_select('status', 'Em Andamento') ?>>Em Andamento</option>
                                                <option value="Finalizado" <?= set_select('status', 'Finalizado') ?>>Finalizado</option>
                                                <option value="Faturado" <?= set_select('status', 'Faturado') ?>>Faturado</option>
                                                <option value="Cancelado" <?= set_select('status', 'Cancelado') ?>>Cancelado</option>
                                            </select>
                                        </div>
                                        <div class="span3">
                                            <label for="dataInicial">Data Inicial<span class="required">*</span></label>
                                            <input id="dataInicial" autocomplete="off" class="span12 datepicker" type="text" name="dataInicial" value="<?= set_value('dataInicial', date('d/m/Y')) ?>" />
                                        </div>
                                        <div class="span3">
                                            <label for="dataFinal">Data Final<span class="required">*</span></label>
                                            <input id="dataFinal" autocomplete="off" class="span12 datepicker" type="text" name="dataFinal" value="<?= set_value('dataFinal') ?>" />
                                        </div>
                                        <div class="span3">
                                            <label for="garantia">Garantia (dias)</label>
                                            <input id="garantia" type="number" placeholder="Status s/g inserir nº/0" min="0" max="9999" class="span12" name="garantia" value="<?= set_value('garantia') ?>" />
                                            <?php echo form_error('garantia'); ?>
                                            <label for="termoGarantia">Termo Garantia</label>
                                            <input id="termoGarantia" class="span12" type="text" name="termoGarantia" value="<?= set_value('termoGarantia') ?>" />
                                            <input id="garantias_id" class="span12" type="hidden" name="garantias_id" value="<?= set_value('garantias_id') ?>" />
                                        </div>
                                    </div>
                                    <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vCliente')) { ?>
                                    <div class="span12" style="padding:1%;margin-left:0"><details class="mapos-card" open><summary>Registrar estado e fotos do equipamento na entrada</summary>
                                        <?php $this->load->view('equipamentos/vistoria_campos', ['vistoriaCreation' => true]); ?>
                                        <p>Se houver erro ao salvar, selecione os arquivos novamente; os navegadores não restauram fotos automaticamente.</p>
                                    </details></div>
                                    <?php } ?>
                                    <div class="span6" style="padding: 1%; margin-left: 0">
                                        <label for="descricaoProduto">
                                            <h4>Descrição Produto/Serviço</h4>
                                        </label>
                                        <textarea class="span12 editor" name="descricaoProduto" id="descricaoProduto" cols="30" rows="5"><?= set_value('descricaoProduto') ?></textarea>
                                    </div>
                                    <div class="span6" style="padding: 1%; margin-left: 0">
                                        <label for="defeito">
                                            <h4>Defeito</h4>
                                        </label>
                                        <textarea class="span12 editor" name="defeito" id="defeito" cols="30" rows="5"><?= set_value('defeito') ?></textarea>
                                    </div>
                                    <div class="span6" style="padding: 1%; margin-left: 0">
                                        <label for="observacoes">
                                            <h4>Observações</h4>
                                        </label>
                                        <textarea class="span12 editor" name="observacoes" id="observacoes" cols="30" rows="5"><?= set_value('observacoes') ?></textarea>
                                    </div>
                                    <div class="span6" style="padding: 1%; margin-left: 0">
                                        <label for="laudoTecnico">
                                            <h4>Laudo Técnico</h4>
                                        </label>
                                        <textarea class="span12 editor" name="laudoTecnico" id="laudoTecnico" cols="30" rows="5"><?= set_value('laudoTecnico') ?></textarea>
                                    </div>
                                    <div class="span12" style="padding: 1%; margin-left: 0">
                                        <div class="span12" style="display:flex; justify-content: center;">
                                            <button class="button btn btn-success" id="btnContinuar">
                                              <span class="button__icon"><i class='bx bx-chevrons-right'></i></span><span class="button__text2">Continuar</span></button>
                                            <a href="<?php echo base_url() ?>index.php/os" class="button btn btn-mini btn-warning" style="max-width: 160px">
                                              <span class="button__icon"><i class="bx bx-undo"></i></span><span class="button__text2">Voltar</span></a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                .
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $("#cliente").autocomplete({
            source: "<?php echo base_url(); ?>index.php/os/autoCompleteCliente",
            minLength: 1,
            select: function(event, ui) {
                $("#clientes_id").val(ui.item.id).trigger('change');
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
        window.MaposInspectionPhotos.configureValidation(document.getElementById('formOs'));
        $(".datepicker").datepicker({
            dateFormat: 'dd/mm/yy'
        });
        $('.editor').trumbowyg({
            lang: 'pt_br',
            semantic: { 'strikethrough': 's', }
        });
    });
</script>
