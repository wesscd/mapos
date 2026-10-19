<?php foreach ($aprovacoes ?? [] as $approval) { ?>
<section class="alert alert-info" aria-label="Aprovação do orçamento">
    <h3>Orçamento #<?= (int) $approval->id ?> — <?= html_escape(ucfirst($approval->status)) ?></h3>
    <?php $snapshot = json_decode($approval->snapshot, true); ?>
    <ul><?php foreach (array_merge($snapshot['produtos'] ?? [], $snapshot['servicos'] ?? []) as $item) { ?>
        <li><?= html_escape($item['descricao'] ?? $item['nome']) ?> · <?= html_escape($item['quantidade']) ?> × R$ <?= html_escape(number_format($item['preco'], 2, ',', '.')) ?></li>
    <?php } ?></ul>
    <p>Valor total com descontos: <strong>R$ <?= html_escape(number_format($approval->valor, 2, ',', '.')) ?></strong></p>
    <?php if ($approval->status === 'pendente') { ?>
    <form method="post" action="<?= site_url('mine/responderOrcamento/' . (int) $result->idOs) ?>">
        <input type="hidden" name="<?= html_escape($this->security->get_csrf_token_name()) ?>" value="<?= html_escape($this->security->get_csrf_hash()) ?>">
        <input type="hidden" name="aprovacao_id" value="<?= (int) $approval->id ?>">
        <p>Ao aprovar, você autoriza os itens e o valor desta versão do orçamento.</p>
        <button type="submit" name="resposta" value="aprovado" class="btn btn-success">Aprovar orçamento</button>
        <button type="submit" name="resposta" value="rejeitado" class="btn">Recusar orçamento</button>
    </form>
    <?php } ?>
</section>
<?php } ?>
