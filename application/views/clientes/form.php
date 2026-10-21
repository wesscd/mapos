<?php
$this->load->helper('form');
$customerEditing = $customerEditing ?? false;
$result = $result ?? null;
$customerValue = function ($field) use ($customerEditing, $result) {
    return set_value($field, $customerEditing ? (string) ($result->$field ?? '') : '');
};
?>
<script src="<?= base_url('assets/js/jquery.mask.min.js') ?>"></script>
<script src="<?= base_url('assets/js/sweetalert2.all.min.js') ?>"></script>
<script src="<?= base_url('assets/js/funcoes.js') ?>"></script>
<section class="ui-page" aria-labelledby="customer-title">
    <header class="ui-page-header"><div><p class="ui-eyebrow">CADASTROS</p><h1 id="customer-title"><?= $customerEditing ? 'Editar cliente / fornecedor' : 'Cadastrar cliente / fornecedor' ?></h1><p>Organize os dados de identificação, contato e endereço.</p></div><a class="ui-button" href="<?= site_url('clientes') ?>">Voltar aos clientes</a></header>
    <div class="ui-card">
        <?php if ($custom_error) { ?><div class="ui-notice ui-notice-error" role="alert"><?= $custom_error ?></div><?php } ?>
        <p class="ui-hint">Os campos com * são obrigatórios.</p>
        <?= form_open(current_url(), ['id' => 'formCliente', 'data-states-url' => base_url('assets/json/estados.json')]) ?>
        <?php if ($customerEditing) {
            echo form_hidden('idClientes', (int) $result->idClientes);
        } ?>
        <fieldset class="ui-fieldset"><legend>Identificação</legend><div class="ui-form-grid">
            <div class="control-group ui-field-wide"><label for="nomeCliente">Nome / razão social *</label><div class="controls"><input id="nomeCliente" name="nomeCliente" type="text" required value="<?= $customerValue('nomeCliente') ?>" autocomplete="name"></div></div>
            <div class="control-group"><label for="documento">CPF/CNPJ</label><div class="controls ui-input-action"><input id="documento" name="documento" class="cpfcnpj" type="text" value="<?= $customerValue('documento') ?>"><button id="buscar_info_cnpj" class="ui-button" type="button">Consultar CNPJ</button></div></div>
            <div class="control-group"><label for="contato">Pessoa de contato</label><div class="controls"><input id="contato" name="contato" class="contato" type="text" value="<?= $customerValue('contato') ?>"></div></div>
        </div><div class="ui-checkbox-row"><label for="fornecedor"><input id="fornecedor" name="fornecedor" type="checkbox" value="1" <?= set_checkbox('fornecedor', '1', ! $this->input->post() && $customerEditing && (int) $result->fornecedor === 1) ?>> Este cadastro é de um fornecedor</label></div></fieldset>
        <fieldset class="ui-fieldset"><legend>Contato</legend><div class="ui-form-grid">
            <div class="control-group"><label for="telefone">Telefone</label><div class="controls"><input id="telefone" name="telefone" type="tel" value="<?= $customerValue('telefone') ?>" autocomplete="tel"></div></div>
            <div class="control-group"><label for="celular">Celular / WhatsApp</label><div class="controls"><input id="celular" name="celular" type="tel" value="<?= $customerValue('celular') ?>" aria-describedby="customer-phone-help"></div><p id="customer-phone-help" class="ui-hint">Informe o DDD e confira os dígitos antes de salvar.</p></div>
            <div class="control-group ui-field-wide"><label for="email">E-mail</label><div class="controls"><input id="email" name="email" type="email" value="<?= $customerValue('email') ?>" autocomplete="off"></div></div>
        </div></fieldset>
        <fieldset class="ui-fieldset"><legend>Acesso à área do cliente</legend><div class="ui-form-grid">
            <div class="control-group"><label for="senha"><?= $customerEditing ? 'Nova senha (opcional)' : 'Senha (opcional)' ?></label><div class="controls ui-input-action"><input id="senha" name="senha" type="password" autocomplete="new-password" aria-describedby="customer-password-help"><button id="customer-password-toggle" class="ui-button" type="button" aria-controls="senha" aria-pressed="false">Mostrar senha</button></div><p id="customer-password-help" class="ui-hint"><?= $customerEditing ? 'Deixe em branco para manter a senha atual.' : 'Senha para o cliente acessar e acompanhar seus atendimentos.' ?></p></div>
        </div></fieldset>
        <fieldset class="ui-fieldset"><legend>Endereço</legend><div class="ui-form-grid">
            <div class="control-group"><label for="cep">CEP</label><div class="controls"><input id="cep" name="cep" type="text" inputmode="numeric" value="<?= $customerValue('cep') ?>" autocomplete="postal-code"></div></div>
            <div class="control-group"><label for="rua">Rua / logradouro</label><div class="controls"><input id="rua" name="rua" type="text" value="<?= $customerValue('rua') ?>" autocomplete="address-line1"></div></div>
            <div class="control-group"><label for="numero">Número</label><div class="controls"><input id="numero" name="numero" type="text" value="<?= $customerValue('numero') ?>"></div></div>
            <div class="control-group"><label for="complemento">Complemento</label><div class="controls"><input id="complemento" name="complemento" type="text" value="<?= $customerValue('complemento') ?>" autocomplete="address-line2"></div></div>
            <div class="control-group"><label for="bairro">Bairro</label><div class="controls"><input id="bairro" name="bairro" type="text" value="<?= $customerValue('bairro') ?>"></div></div>
            <div class="control-group"><label for="cidade">Cidade</label><div class="controls"><input id="cidade" name="cidade" type="text" value="<?= $customerValue('cidade') ?>" autocomplete="address-level2"></div></div>
            <div class="control-group"><label for="estado">Estado</label><div class="controls"><select id="estado" name="estado" data-selected="<?= $customerValue('estado') ?>" autocomplete="address-level1"><option value="">Selecione o estado</option></select></div></div>
        </div></fieldset>
        <footer class="ui-form-actions"><button type="submit" class="ui-button ui-button-primary"><?= $customerEditing ? 'Salvar alterações' : 'Salvar cadastro' ?></button><a class="ui-button" href="<?= site_url('clientes') ?>">Cancelar</a></footer>
        </form>
    </div>
</section>
<script src="<?= base_url('assets/js/jquery.validate.js') ?>"></script>
<script src="<?= base_url('assets/js/customer-form.js') ?>?v=1" defer></script>
