<dl class="os-totals"><div><dt>Itens</dt><dd>R$ <?= html_escape(number_format($osGross, 2, ',', '.')) ?></dd></div><div><dt>Desconto</dt><dd>R$ <?= html_escape(number_format($osGross - $osNet, 2, ',', '.')) ?></dd></div><div><dt>Total a pagar</dt><dd data-os-net>R$ <?= html_escape(number_format($osNet, 2, ',', '.')) ?></dd></div></dl>
<?php foreach (['service' => ['Serviços', $servicos], 'product' => ['Produtos', $produtos]] as $kind => $group) {
    $isService = $kind === 'service'; ?>
<section class="os-budget-section"><h3><?= html_escape($group[0]) ?></h3>
<?php if ($osCanMutate) { ?>
<form id="<?= $isService ? 'formServicos' : 'formProdutos' ?>" method="post" action="<?= site_url('os/' . ($isService ? 'adicionarServico' : 'adicionarProduto')) ?>" data-os-ajax data-os-message="Item adicionado ao orçamento.">
    <input type="hidden" name="<?= $isService ? 'idOsServico' : 'idOsProduto' ?>" value="<?= (int) $result->idOs ?>">
    <input type="hidden" name="<?= $isService ? 'idServico' : 'idProduto' ?>" id="<?= $isService ? 'idServico' : 'idProduto' ?>">
    <div class="os-item-fields"><label for="<?= $kind ?>-name"><?= $isService ? 'Serviço' : 'Produto' ?><input id="<?= $kind ?>-name" name="<?= $isService ? 'servico' : 'produto' ?>" autocomplete="off" required placeholder="Busque e selecione nas sugestões" data-os-catalog="<?= $isService ? 'Servico' : 'Produto' ?>" data-os-id="<?= $isService ? 'idServico' : 'idProduto' ?>" data-os-price="<?= $kind ?>-price"></label>
    <label for="<?= $kind ?>-price">Preço unitário (R$)<input id="<?= $kind ?>-price" name="preco" type="number" step="0.01" min="0" required></label>
    <label for="<?= $kind ?>-quantity">Quantidade<input id="<?= $kind ?>-quantity" name="quantidade" type="number" min="<?= $isService ? '0.0001' : '1' ?>" step="<?= $isService ? '0.0001' : '1' ?>" value="1" required></label>
    <button type="submit" class="btn btn-primary">Adicionar <?= $isService ? 'serviço' : 'produto' ?></button></div>
</form><?php } ?>
<div class="ui-table-scroll" tabindex="0" role="region" aria-label="Dados da ordem de serviço"><table class="ui-table" id="<?= $isService ? 'tblServicos' : 'tblProdutos' ?>"><thead><tr><th><?= $isService ? 'Serviço' : 'Produto' ?></th><th>Quantidade</th><th>Preço unitário</th><th>Subtotal</th><?php if ($osCanMutate) { ?><th>Ações</th><?php } ?></tr></thead><tbody>
<?php foreach ($group[1] as $item) {
    $price = $item->preco ?? $item->precoVenda;
    $quantity = (float) $item->quantidade; ?>
<tr><td><?= html_escape($isService ? $item->nome : $item->descricao) ?></td><td><?= html_escape((string) $quantity) ?></td><td>R$ <?= html_escape(number_format($price, 2, ',', '.')) ?></td><td>R$ <?= html_escape(number_format(round($price * $quantity, 2), 2, ',', '.')) ?></td>
<?php if ($osCanMutate) { ?><td><form method="post" action="<?= site_url('os/' . ($isService ? 'excluirServico' : 'excluirProduto')) ?>" data-os-ajax data-os-confirm="Remover este item do orçamento?" data-os-message="Item removido do orçamento."><input type="hidden" name="idOs" value="<?= (int) $result->idOs ?>"><input type="hidden" name="<?= $isService ? 'idServico' : 'idProduto' ?>" value="<?= (int) ($isService ? $item->idServicos_os : $item->idProdutos_os) ?>"><button type="submit" class="btn btn-danger btn-small">Remover <?= $isService ? 'serviço' : 'produto' ?></button></form></td><?php } ?></tr>
<?php } ?><?php if (! $group[1]) { ?><tr><td colspan="<?= $osCanMutate ? 5 : 4 ?>">Nenhum item adicionado.</td></tr><?php } ?></tbody></table></div></section>
<?php } ?>
<?php if ($osCanMutate) { ?>
<details class="os-inline-action" id="os-discount"><summary>Aplicar ou alterar desconto</summary>
<form id="formDesconto" method="post" action="<?= site_url('os/adicionarDesconto') ?>" data-os-ajax data-os-message="Desconto atualizado.">
<input type="hidden" name="idOs" value="<?= (int) $result->idOs ?>"><input type="hidden" name="valorTotal" value="<?= html_escape((string) $osGross) ?>">
<div class="os-field-grid"><label for="discount-kind">Tipo de desconto<select name="tipoDesconto" id="discount-kind"><option value="real">Valor em reais (R$)</option><option value="porcento" <?= $result->tipo_desconto === 'porcento' ? 'selected' : '' ?>>Percentual (%)</option></select></label>
<label for="discount-value">Desconto<input name="desconto" id="discount-value" type="number" min="0" step="0.01" required value="<?= html_escape((string) ($result->desconto ?: 0)) ?>"></label></div>
<p>Total após desconto: <strong><output id="discount-preview" aria-live="polite">R$ <?= html_escape(number_format($osNet, 2, ',', '.')) ?></output></strong></p><button class="btn btn-primary" type="submit">Aplicar desconto</button>
</form></details><?php } ?>
