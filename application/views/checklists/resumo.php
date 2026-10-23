<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Procedimentos da OS #<?= (int) $order->idOs ?></title><style>body{font:15px/1.6 Arial,sans-serif;color:#17243a;max-width:900px;margin:32px auto;padding:0 20px}section{border-top:1px solid #ccc;padding:16px 0}small{color:#546174}@media print{button{display:none}body{margin:0;max-width:none}section{break-inside:avoid}}</style></head><body>
<button type="button" onclick="window.print()">Imprimir resumo</button><h1>Procedimentos registrados · OS #<?= (int) $order->idOs ?></h1><p>Resumo dos procedimentos marcados como realizados. Este documento não acrescenta valores ao orçamento, nem comprova pagamento ou entrega do equipamento.</p>
<?php $count = 0;
foreach ($groups as $group) {
    $done = array_filter($group->itens, fn ($item) => $item->fase === 'execucao' && $item->resultado === 'realizado');
    if (! $done) {
        continue;
    } ?>
<section><h2><?= html_escape($group->equipamento_nome) ?></h2><p><small><?= html_escape($group->modelo_nome) ?> · versão <?= (int) $group->modelo_versao ?></small></p><ul><?php foreach ($done as $item) {
    $count++; ?><li><?= html_escape($item->descricao) ?><br><small><?= html_escape($item->updated_at) ?> · <?= html_escape($item->responsavel ?? 'Responsável não informado') ?></small></li><?php } ?></ul></section>
<?php } if (! $count) { ?><p>Nenhum procedimento de execução registrado como realizado.</p><?php } ?>
<p><small>Observações internas, referências de autorização e fotos não são incluídas neste resumo.</small></p></body></html>
