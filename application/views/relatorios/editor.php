<link rel="stylesheet" href="<?= base_url('assets/css/technical-report.css') ?>?v=2">
<section class="ui-page report-editor">
<header class="ui-page-header"><div><p class="ui-eyebrow">OS #<?= (int) $source['os'] ?></p><h1>Preparar relatório técnico</h1><p>Revise o conteúdo destinado ao cliente. Os textos deste documento não alteram a OS.</p></div><a class="ui-button" href="<?= site_url('os/visualizar/' . (int) $source['os']) ?>#os-pane-execucao">Voltar à OS</a></header>
<p class="ui-notice">Observações internas, anotações, referências de autorização e legendas internas das fotos não são copiadas. Confira também se os textos e as imagens escolhidas podem ser entregues ao cliente. O relatório não envia mensagens.</p>
<?php if (! empty($reportError)) { ?><p class="alert alert-error" role="alert"><?= html_escape($reportError) ?></p><?php } ?>
<?= form_open('relatoriotecnico/preparar/' . (int) $source['os']) ?>
<input type="hidden" name="fonte" value="<?= html_escape($source['hash']) ?>">
<section class="ui-card"><h2>Textos para o cliente</h2><p>Cliente: <?= html_escape($source['cliente']) ?> · Responsável técnico: <?= html_escape($source['tecnico']) ?></p>
<?php foreach (['problema' => ['Problema relatado', 4000], 'diagnostico' => ['Diagnóstico explicado ao cliente', 6000], 'recomendacoes' => ['Recomendações e cuidados após o serviço', 4000]] as $field => [$label, $max]) { ?><label><?= html_escape($label) ?><textarea name="<?= $field ?>" rows="5" maxlength="<?= $max ?>" <?= $field !== 'recomendacoes' ? 'required' : '' ?>><?= html_escape($values[$field]) ?></textarea></label><?php } ?>
</section>
<section class="ui-card"><h2>Escopo aprovado</h2><p><?= $source['aprovacao'] ? 'O documento identificará o orçamento aprovado e seus serviços e peças. Essa informação vem da aprovação registrada pelo cliente.' : 'Não há aprovação vigente registrada pelo cliente para este orçamento. O documento informará essa situação; o relatório não autoriza intervenções.' ?></p></section>
<section class="ui-card"><h2>Procedimentos e testes registrados</h2><p>Selecione até 200 resultados para o relatório. Itens pendentes não aparecem nesta lista. As observações internas de cada item não são incluídas.</p>
<?php if (! $source['resultados']) { ?><p>Nenhum resultado de checklist registrado. O relatório não afirmará que os testes foram concluídos.</p><?php } ?>
<?php foreach ($source['resultados'] as $key => $item) { ?><label class="report-choice"><input type="checkbox" name="resultados[]" value="<?= (int) $key ?>" <?= in_array((string) $key, array_map('strval', $values['resultados']), true) ? 'checked' : '' ?>> <?= html_escape($item['equipamento'] . ' - ' . $item['descricao'] . ': ' . $item['resultado']) ?></label><?php } ?></section>
<section class="ui-card"><h2>Fotos que acompanharão o documento</h2><p>Nenhuma foto é selecionada automaticamente. Escolha até seis imagens das vistorias ou dos checklists desta OS.</p>
<?php if (! $source['fotos']) { ?><p>Nenhuma foto disponível neste atendimento.</p><?php } ?>
<div class="report-photo-picker"><?php foreach ($source['fotos'] as $key => $photo) { ?><label class="report-choice"><input type="checkbox" name="fotos[]" value="<?= html_escape($key) ?>" <?= in_array($key, $values['fotos'], true) ? 'checked' : '' ?>><img loading="lazy" src="<?= html_escape($photo['url']) ?>" alt="<?= html_escape($photo['legenda']) ?>"><span><?= html_escape($photo['legenda']) ?></span></label><?php } ?></div></section>
<button class="ui-button ui-button-primary" type="submit">Gerar prévia para conferência</button>
</form></section>
