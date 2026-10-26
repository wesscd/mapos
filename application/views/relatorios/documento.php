<div class="technical-report">
<div class="report-brand"><?= html_escape($report['empresa']) ?></div><h1>Relatório técnico</h1>
<p class="report-meta">OS #<?= (int) $report['os'] ?> · <?= html_escape($report['status']) ?> · Preparado em <?= html_escape($report['gerado_em']) ?></p>
<table class="report-identification"><tr><td><strong>Cliente</strong><br><?= html_escape($report['cliente']) ?></td><td><strong>Responsável técnico</strong><br><?= html_escape($report['tecnico']) ?></td></tr></table>
<h2>Equipamento / atendimento</h2>
<?php if (! $report['equipamentos']) { ?><p>Atendimento sem equipamento vinculado.</p><?php } ?>
<?php foreach ($report['equipamentos'] as $equipment) { ?><p><?= html_escape(implode(' · ', array_filter([$equipment['equipamento'], $equipment['marca'], $equipment['modelo']]))) ?><br><small>Série: <?= html_escape($equipment['num_serie'] ?: 'Não informada') ?></small></p><?php } ?>
<?php foreach (['problema' => 'Problema relatado', 'diagnostico' => 'Diagnóstico', 'recomendacoes' => 'Recomendações e cuidados'] as $field => $title) {
    if ($field === 'recomendacoes') {
        continue;
    } ?><h2><?= html_escape($title) ?></h2><p><?= nl2br(html_escape($report[$field])) ?></p><?php } ?>
<h2>Escopo do orçamento aprovado</h2>
<?php if ($report['aprovacao']) { ?><p>Aprovação #<?= (int) $report['aprovacao']['id'] ?> registrada pelo cliente em <?= html_escape($report['aprovacao']['data']) ?>.</p><ul><?php foreach (['servicos' => 'Serviço', 'produtos' => 'Peça / produto'] as $kind => $label) {
    foreach ($report['aprovacao'][$kind] as $line) { ?><li><?= html_escape($label . ': ' . ($line['nome'] ?? $line['descricao']) . ' - Quantidade: ' . $line['quantidade']) ?></li><?php }
    } ?></ul>
<?php } else { ?><p>Não há aprovação vigente registrada pelo cliente para este orçamento. Este relatório não autoriza serviços adicionais.</p><?php } ?>
<?php foreach (['procedimentos' => 'Procedimentos registrados', 'testes' => 'Testes e resultados'] as $kind => $title) {
    $rows = array_filter($report['resultados'], fn ($item) => $item['secao'] === $kind); ?>
<h2><?= html_escape($title) ?></h2>
<?php if (! $rows) { ?><p><?= $kind === 'testes' ? 'Nenhum resultado de teste selecionado para este documento. Isso não confirma a conclusão dos testes.' : 'Nenhum procedimento selecionado para este documento.' ?></p><?php } else { ?><table class="report-results"><thead><tr><th>Equipamento / procedimento</th><th>Resultado</th></tr></thead><tbody><?php foreach ($rows as $item) { ?><tr><td><?= html_escape($item['equipamento']) ?><br><strong><?= html_escape($item['descricao']) ?></strong></td><td><?= html_escape($item['resultado']) ?></td></tr><?php } ?></tbody></table><?php }
} ?>
<?php if ($report['recomendacoes'] !== '') { ?><h2>Recomendações e cuidados</h2><p><?= nl2br(html_escape($report['recomendacoes'])) ?></p><?php } ?>
<?php if ($report['fotos']) { ?><h2>Registro fotográfico selecionado</h2><?php foreach ($report['fotos'] as $key => $photo) { ?><div class="report-photo"><img src="<?= html_escape($images[$key] ?? $photo['url']) ?>" alt="<?= html_escape($photo['legenda']) ?>"><p><?= html_escape($photo['legenda']) ?></p></div><?php }
} ?>
<p class="report-disclaimer">Documento técnico preparado pela equipe a partir dos registros disponíveis e do conteúdo revisado. Não acrescenta cobranças, não comprova pagamento ou entrega e não substitui o orçamento aprovado nem o termo de garantia.</p>
</div>
