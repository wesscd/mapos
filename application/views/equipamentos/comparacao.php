<details class="mapos-inspection-comparison"><summary>Comparar fotos de entrada e saída por posição</summary>
    <p>Compare as condições aparentes. A ausência de foto não comprova a ausência de marcas ou danos.</p>
    <?php foreach (Vistoria_service::ANGLES as $angle => $label) { ?>
        <h5><?= html_escape($label) ?></h5><div class="mapos-comparison-grid">
            <?php foreach (['Entrada' => $entryInspection, 'Saída' => $exitInspection] as $phase => $inspectionGroup) {
                $anglePhotos = array_filter($inspectionGroup['fotos'] ?? [], fn ($photo) => $photo['perspectiva'] === $angle); ?>
                <section><h6><?= html_escape($phase) ?></h6><div class="mapos-inspection-photos">
                    <?php foreach ($anglePhotos as $photo) { ?><figure><a target="_blank" rel="noopener" href="<?= site_url('equipamentos/vistoriaFoto/' . (int) $photo['id']) ?>"><img loading="lazy" src="<?= site_url('equipamentos/vistoriaFoto/' . (int) $photo['id']) ?>" alt="<?= html_escape($phase . ' · ' . $label) ?>"></a><figcaption><?= html_escape($photo['observacao']) ?><br><?= html_escape(date('d/m/Y H:i', strtotime($photo['created_at']))) ?></figcaption></figure><?php } ?>
                </div><?php if (! $anglePhotos) { ?><p>Sem foto desta posição.</p><?php } ?></section>
            <?php } ?>
        </div>
    <?php } ?>
    <?php if (! empty($entryPhotos)) { ?><h5>Outras fotos preservadas no recebimento</h5><div class="mapos-inspection-photos"><?php foreach ($entryPhotos as $photoId) { ?><figure><a target="_blank" rel="noopener" href="<?= site_url('equipamentos/foto/' . (int) $photoId) ?>"><img loading="lazy" src="<?= site_url('equipamentos/foto/' . (int) $photoId) ?>" alt="Foto disponível no recebimento #<?= (int) $photoId ?>"></a></figure><?php } ?></div><?php } ?>
</details>
