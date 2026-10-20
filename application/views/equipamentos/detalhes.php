<section class="mapos-workflow"><div class="mapos-section-heading"><div><span class="mapos-eyebrow">EQUIPAMENTO #<?= (int) $equipment->idEquipamentos ?></span><h1><?= html_escape($equipment->equipamento) ?></h1></div>
    <a class="btn" href="<?= site_url('equipamentos/etiqueta/' . (int) $equipment->idEquipamentos) ?>" target="_blank" rel="noopener">Imprimir etiqueta QR</a>
</div><p>Cliente: <strong><?= html_escape($equipment->nomeCliente) ?></strong></p>
    <p><?= html_escape(trim($equipment->marca . ' ' . $equipment->modelo)) ?> · Série: <?= html_escape($equipment->num_serie ?: 'Não informada') ?> · Cor: <?= html_escape($equipment->cor) ?></p>
    <p><?= html_escape($equipment->descricao) ?></p>
    <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'eCliente')) { ?>
        <a class="btn" href="<?= site_url('equipamentos/salvar/' . (int) $equipment->idEquipamentos) ?>">Editar cadastro</a>
        <?= form_open_multipart('equipamentos/adicionarFoto/' . (int) $equipment->idEquipamentos) ?><label>Adicionar foto (JPEG/PNG, até 2 MB e 12 MP; limite de 12 fotos)<input type="file" name="foto" accept="image/jpeg,image/png" required></label><button class="btn">Registrar foto</button></form>
    <?php } ?>
    <div class="mapos-grid"><?php foreach ($photos as $photo) { ?><figure class="mapos-card"><a href="<?= site_url('equipamentos/foto/' . (int) $photo['id']) ?>" target="_blank" rel="noopener"><img style="max-width:100%;max-height:260px" src="<?= site_url('equipamentos/foto/' . (int) $photo['id']) ?>" alt="Foto registrada do equipamento #<?= (int) $equipment->idEquipamentos ?>" loading="lazy"></a><figcaption><?= html_escape(date('d/m/Y H:i', strtotime($photo['created_at']))) ?></figcaption></figure><?php } ?></div>
    <?php if (isset($history)) { ?><h2>Histórico de atendimentos</h2><p>Últimos 100 atendimentos vinculados a este cadastro.</p>
        <?php foreach ($history as $order) { ?><p><a href="<?= site_url('os/visualizar/' . (int) $order->idOs) ?>">OS #<?= (int) $order->idOs ?></a> · <?= html_escape($order->status) ?> · <?= html_escape($order->dataInicial) ?><?= $order->entregue_em ? ' · Entrega: ' . html_escape($order->entregue_em) : '' ?></p><?php } ?>
        <?php if (! $history) { ?><p>Ainda não há OS vinculadas. Abra uma OS do cliente e use a seção Equipamentos.</p><?php } ?>
    <?php } ?>
</section>
