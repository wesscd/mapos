<?php
$this->load->helper('form');
$clientEscape = function ($value) {
    return html_escape(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
};
$clientPermission = $this->session->userdata('permissao');
?>
<section class="ui-page" aria-labelledby="customers-title">
    <header class="ui-page-header"><div><p class="ui-eyebrow">CADASTROS</p><h1 id="customers-title">Clientes e fornecedores</h1><p>Consulte os contatos e acesse os atendimentos de cada cliente.</p></div>
    <?php if ($this->permission->checkPermission($clientPermission, 'aCliente')) { ?><a class="ui-button ui-button-primary" href="<?= site_url('clientes/adicionar') ?>">Cadastrar cliente / fornecedor</a><?php } ?></header>
    <div class="ui-card">
        <form class="ui-search" method="get" action="<?= site_url('clientes') ?>" role="search"><div><label for="pesquisa">Buscar cliente ou fornecedor</label><input type="search" name="pesquisa" id="pesquisa" placeholder="Nome, CPF/CNPJ, e-mail ou telefone" value="<?= html_escape($clientSearch) ?>"></div><button type="submit" class="ui-button ui-button-primary">Buscar</button><?php if ($clientSearch !== '') { ?><a class="ui-button" href="<?= site_url('clientes') ?>">Limpar busca</a><?php } ?></form>
        <p class="ui-table-hint">Deslize a tabela para ver os contatos e as ações.</p>
        <div class="ui-table-scroll" role="region" aria-label="Clientes e fornecedores" tabindex="0">
        <table id="customers-table" class="ui-table"><caption class="ui-sr-only">Clientes e fornecedores<?= $clientSearch !== '' ? ' encontrados na busca' : ' cadastrados' ?></caption><thead><tr><th scope="col">Cliente / fornecedor</th><th scope="col">Contato</th><th scope="col">Telefones</th><th scope="col">E-mail</th><th scope="col">Tipo</th><th scope="col">Ações</th></tr></thead><tbody>
        <?php if (! $results) { ?><tr><td colspan="6" class="ui-empty"><?= $clientSearch !== '' ? 'Nenhum resultado para esta busca. Tente outro nome, documento ou telefone.' : 'Nenhum cliente ou fornecedor cadastrado.' ?></td></tr><?php } ?>
        <?php foreach ($results as $r) { ?>
        <tr><td><a class="ui-name" href="<?= site_url('clientes/visualizar/' . (int) $r->idClientes) ?>"><?= $clientEscape($r->nomeCliente) ?></a><small>#<?= (int) $r->idClientes ?><?= $r->documento ? ' · ' . $clientEscape($r->documento) : '' ?></small></td>
        <td><?= $clientEscape($r->contato ?: '—') ?></td><td><span><?= $clientEscape($r->telefone ?: '—') ?></span><?php if ($r->celular) { ?><small>Celular: <?= $clientEscape($r->celular) ?></small><?php } ?></td><td><?= $clientEscape($r->email ?: '—') ?></td><td><span class="ui-badge"><?= (int) $r->fornecedor === 1 ? 'Fornecedor' : 'Cliente' ?></span></td>
        <td><div class="ui-row-actions">
        <?php if ($this->permission->checkPermission($clientPermission, 'vCliente')) { ?><a class="ui-button ui-button-small" href="<?= site_url('clientes/visualizar/' . (int) $r->idClientes) ?>" aria-label="Ver cadastro de <?= $clientEscape($r->nomeCliente) ?>">Ver</a><a class="ui-button ui-button-small" href="<?= html_escape(site_url('mine') . '?e=' . rawurlencode($r->email)) ?>" target="_blank" rel="noopener" aria-label="Abrir portal de <?= $clientEscape($r->nomeCliente) ?>">Portal</a><?php } ?>
        <?php if ($this->permission->checkPermission($clientPermission, 'eCliente')) { ?><a class="ui-button ui-button-small" href="<?= site_url('clientes/editar/' . (int) $r->idClientes) ?>" aria-label="Editar cadastro de <?= $clientEscape($r->nomeCliente) ?>">Editar</a><?php } ?>
        <?php if ($this->permission->checkPermission($clientPermission, 'dCliente')) { ?><a href="#modal-excluir" role="button" data-toggle="modal" data-customer-id="<?= (int) $r->idClientes ?>" class="ui-button ui-button-small ui-button-danger" aria-label="Excluir cadastro de <?= $clientEscape($r->nomeCliente) ?>">Excluir</a><?php } ?>
        </div></td></tr><?php } ?>
        </tbody></table></div>
        <nav class="ui-pagination" aria-label="Páginas de clientes e fornecedores"><?= $this->pagination->create_links() ?></nav>
    </div>
</section>
<div id="modal-excluir" class="modal hide fade ui-modal" tabindex="-1" role="dialog" aria-labelledby="delete-customer-title" aria-hidden="true">
    <?= form_open('clientes/excluir') ?>
    <div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Fechar">×</button><h2 id="delete-customer-title">Excluir cliente</h2></div>
    <div class="modal-body"><input type="hidden" id="idCliente" name="id" value=""><p>Deseja excluir este cliente? Esta ação também exclui os dados associados a ele, incluindo ordens de serviço, vendas e receitas.</p></div>
    <div class="modal-footer"><button type="button" class="ui-button" data-dismiss="modal">Cancelar</button><button type="submit" class="ui-button ui-button-danger">Excluir cliente e dados associados</button></div></form>
</div>
<script>
$(function () {
    var customerTrigger;
    $('[data-customer-id]').on('click', function () { customerTrigger = this; $('#idCliente').val($(this).attr('data-customer-id')); });
    $('#modal-excluir').on('shown', function () { $(this).find('[data-dismiss="modal"]').first().focus(); });
    $('#modal-excluir').on('hidden', function () { $('#idCliente').val(''); if (customerTrigger) { customerTrigger.focus(); } });
});
</script>
