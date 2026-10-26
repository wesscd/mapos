<link rel="stylesheet" href="<?= base_url('assets/css/technical-report.css') ?>?v=2">
<section class="ui-page report-editor"><header class="ui-page-header"><div><p class="ui-eyebrow">CONFERÊNCIA DO DOCUMENTO</p><h1>Prévia do relatório técnico</h1><p>Confira os textos, os resultados e as fotos antes de baixar. A prévia vale por 30 minutos e precisa ser refeita se os registros da OS mudarem. Uma nova prévia substitui a anterior.</p></div><a class="ui-button" href="<?= site_url('os/visualizar/' . (int) $report['os']) ?>#os-pane-execucao">Voltar à OS</a></header>
<?php $this->load->view('relatorios/documento', ['report' => $report, 'images' => []]); ?>
<?= form_open('relatoriotecnico/pdf/' . (int) $report['os'], ['id' => 'report-download', 'data-report-os' => (int) $report['os']]) ?><input type="hidden" name="previa" value="<?= html_escape($previewKey) ?>"><label class="report-choice"><input type="checkbox" name="conferido" value="1" required> Revisei o conteúdo e as fotos para entregar este relatório ao cliente.</label><button class="ui-button ui-button-primary" type="submit">Baixar relatório em PDF</button></form>
<p id="report-download-status" role="status" aria-live="polite" hidden></p>
<?= form_open('relatoriotecnico/preparar/' . (int) $report['os']) ?><input type="hidden" name="acao" value="editar"><?php foreach (['problema', 'diagnostico', 'recomendacoes'] as $field) { ?><input type="hidden" name="<?= $field ?>" value="<?= html_escape($report[$field]) ?>"><?php } ?><?php foreach (['fotos', 'resultados'] as $field) {
    foreach (array_keys($report[$field]) as $key) { ?><input type="hidden" name="<?= $field ?>[]" value="<?= html_escape((string) $key) ?>"><?php }
    } ?><button class="ui-button" type="submit">Voltar e ajustar conteúdo</button></form>
</section>

<script src="<?= base_url('assets/js/technical-report.js') ?>?v=1" defer></script>
