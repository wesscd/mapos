<?php
$isProductCatalog = $catalogType === 'produtos';
$catalogTitle = $isProductCatalog ? 'Produtos' : 'Serviços';
$catalogSingle = $isProductCatalog ? 'produto' : 'serviço';
$catalogPermission = $isProductCatalog ? 'Produto' : 'Servico';
$catalogRole = $this->session->userdata('permissao');
?>
<section class="ui-page" aria-labelledby="catalog-title">
    <header class="ui-page-header"><div><p class="ui-eyebrow">CATÁLOGO</p><h1 id="catalog-title"><?= $catalogTitle ?></h1><p><?= $isProductCatalog ? 'Consulte os preços, o estoque e os dados dos produtos.' : 'Organize os serviços oferecidos e seus preços de referência.' ?></p></div><div class="ui-row-actions">
    <?php if ($this->permission->checkPermission($catalogRole, 'a' . $catalogPermission)) { ?><a class="ui-button ui-button-primary" href="<?= site_url($catalogType . '/adicionar') ?>">Cadastrar <?= $catalogSingle ?></a><?php if ($isProductCatalog) { ?><button type="button" class="ui-button" data-toggle="modal" data-target="#modal-etiquetas">Gerar etiquetas</button><?php } ?><?php } ?></div></header>
    <div class="ui-card"><form class="ui-search" method="get" action="<?= site_url($catalogType) ?>" role="search"><div><label for="pesquisa">Buscar <?= $catalogSingle ?></label><input type="search" name="pesquisa" id="pesquisa" placeholder="<?= $isProductCatalog ? 'Nome ou código de barras' : 'Nome ou descrição' ?>" value="<?= html_escape($catalogSearch) ?>"></div><button type="submit" class="ui-button ui-button-primary">Buscar</button><?php if ($catalogSearch !== '') { ?><a class="ui-button" href="<?= site_url($catalogType) ?>">Limpar busca</a><?php } ?></form>
    <p class="ui-table-hint">Deslize a tabela para ver os valores e as ações.</p><div class="ui-table-scroll" role="region" aria-label="<?= $catalogTitle ?>" tabindex="0"><table class="ui-table ui-catalog-table"><caption class="ui-sr-only"><?= $catalogTitle ?> cadastrados</caption><thead><tr><th scope="col"><?= $isProductCatalog ? 'Produto' : 'Serviço' ?></th><th scope="col"><?= $isProductCatalog ? 'Código de barras' : 'Descrição' ?></th><?php if ($isProductCatalog) { ?><th scope="col">Estoque</th><?php } ?><th scope="col">Preço<?= $isProductCatalog ? ' de venda' : '' ?></th><th scope="col">Ações</th></tr></thead><tbody>
    <?php if (! $results) { ?><tr><td colspan="<?= $isProductCatalog ? 5 : 4 ?>" class="ui-empty"><?= $catalogSearch !== '' ? 'Nenhum resultado para esta busca. Tente outro termo.' : 'Nenhum ' . $catalogSingle . ' cadastrado.' ?></td></tr><?php } ?>
    <?php foreach ($results as $r) {
        $catalogId = (int) ($isProductCatalog ? $r->idProdutos : $r->idServicos);
        $catalogName = $isProductCatalog ? $r->descricao : $r->nome;
        ?>
    <tr><td><strong><?= html_escape($catalogName) ?></strong><small>#<?= $catalogId ?></small></td><td><?= html_escape(($isProductCatalog ? $r->codDeBarra : $r->descricao) ?: '—') ?></td><?php if ($isProductCatalog) { ?><td><?= html_escape($r->estoque) ?> <small><?= html_escape($r->unidade) ?></small></td><?php } ?><td class="ui-nowrap">R$ <?= html_escape(number_format((float) ($isProductCatalog ? $r->precoVenda : $r->preco), 2, ',', '.')) ?></td><td><div class="ui-row-actions">
    <?php if ($isProductCatalog && $this->permission->checkPermission($catalogRole, 'vProduto')) { ?><a class="ui-button ui-button-small" href="<?= site_url('produtos/visualizar/' . $catalogId) ?>" aria-label="Ver produto <?= html_escape($catalogName) ?>">Ver</a><?php } ?>
    <?php if ($this->permission->checkPermission($catalogRole, 'e' . $catalogPermission)) { ?><a class="ui-button ui-button-small" href="<?= site_url($catalogType . '/editar/' . $catalogId) ?>" aria-label="Editar <?= $catalogSingle ?> <?= html_escape($catalogName) ?>">Editar</a><?php if ($isProductCatalog) { ?><button type="button" class="ui-button ui-button-small" data-toggle="modal" data-target="#atualizar-estoque" data-item-id="<?= $catalogId ?>" data-stock="<?= html_escape($r->estoque) ?>" aria-label="Adicionar estoque de <?= html_escape($catalogName) ?>">Adicionar estoque</button><?php } ?><?php } ?>
    <?php if ($this->permission->checkPermission($catalogRole, 'd' . $catalogPermission)) { ?><button type="button" class="ui-button ui-button-small ui-button-danger" data-toggle="modal" data-target="#modal-excluir" data-item-id="<?= $catalogId ?>" aria-label="Excluir <?= $catalogSingle ?> <?= html_escape($catalogName) ?>">Excluir</button><?php } ?>
    </div></td></tr><?php } ?></tbody></table></div><nav class="ui-pagination" aria-label="Paginação de <?= $catalogTitle ?>"><?= $this->pagination->create_links() ?></nav></div>
</section>
<div id="modal-excluir" class="modal hide fade ui-modal ui-catalog-modal" tabindex="-1" role="dialog" aria-labelledby="catalog-delete-title" aria-hidden="true">
<?= form_open($catalogType . '/excluir') ?><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Fechar">×</button><h2 id="catalog-delete-title">Excluir <?= $catalogSingle ?></h2></div><div class="modal-body"><input type="hidden" name="id" value=""><p>Deseja excluir este <?= $catalogSingle ?>?</p></div><div class="modal-footer"><button type="button" class="ui-button" data-dismiss="modal">Cancelar</button><button type="submit" class="ui-button ui-button-danger">Excluir <?= $catalogSingle ?></button></div></form></div>
<?php if ($isProductCatalog) {
    $this->load->view('produtos/catalog_modals');
} ?>
<script src="<?= base_url('assets/js/catalog-list.js') ?>?v=1" defer></script>
