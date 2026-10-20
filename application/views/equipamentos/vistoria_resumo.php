<?php if (! empty($inspection)) { ?>
<section class="mapos-inspection"><h4>Vistoria de <?= ($inspection['tipo'] ?? 'entrada') === 'saida' ? 'saída' : 'entrada' ?> #<?= (int) $inspection['id'] ?> · <?= html_escape(Vistoria_service::STATES[$inspection['estado']] ?? $inspection['estado']) ?></h4>
    <p><?= html_escape(date('d/m/Y H:i', strtotime($inspection['created_at']))) ?> · Registrada por <?= html_escape($inspection['responsavel']) ?></p>
    <p><strong>Condições observadas:</strong><br><?= nl2br(html_escape($inspection['condicoes'])) ?></p>
    <p><strong>Acessórios:</strong><br><?= nl2br(html_escape($inspection['acessorios'])) ?></p>
    <?php if (($inspection['tipo'] ?? 'entrada') === 'saida') { ?><p><strong>Conferência dos acessórios:</strong> <?= $inspection['conferencia'] === 'divergencia' ? 'Divergência registrada' : 'Conferidos, sem divergência' ?></p>
        <p><strong>Ressalvas da entrega:</strong><br><?= nl2br(html_escape($inspection['ressalvas'] ?: 'Sem ressalvas registradas.')) ?></p><?php } ?>
    <div class="mapos-inspection-photos"><?php foreach ($inspection['fotos'] as $photo) { ?><figure>
        <a href="<?= site_url('equipamentos/vistoriaFoto/' . (int) $photo['id']) ?>" target="_blank" rel="noopener"><img loading="<?= ! empty($inspectionPrint) ? 'eager' : 'lazy' ?>" src="<?= site_url('equipamentos/vistoriaFoto/' . (int) $photo['id']) ?>" alt="<?= html_escape(Vistoria_service::ANGLES[$photo['perspectiva']] ?? $photo['perspectiva']) ?> do equipamento"></a>
        <figcaption><strong><?= html_escape(Vistoria_service::ANGLES[$photo['perspectiva']] ?? $photo['perspectiva']) ?></strong> · <?= html_escape(date('d/m/Y H:i', strtotime($photo['created_at']))) ?><br><?= html_escape($photo['observacao']) ?></figcaption>
    </figure><?php } ?></div>
    <?php if (! $inspection['fotos']) { ?><p>Não foram registradas fotos nesta vistoria.</p><?php } ?>
</section>
<?php } ?>
