<section class="mapos-workflow">
    <div class="mapos-section-heading"><div><span class="mapos-eyebrow">PATRIMÔNIO DO CLIENTE</span><h1>Equipamentos</h1></div>
        <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'aCliente')) { ?><a class="btn btn-primary" href="<?= site_url('equipamentos/salvar') ?>">Cadastrar equipamento</a><?php } ?>
    </div>
    <p>Identifique cada equipamento e consulte seu histórico de atendimentos.</p>
    <form method="get"><label>Buscar por cliente, equipamento, modelo ou série <input name="q" value="<?= html_escape($query) ?>" maxlength="100"></label><button class="btn">Buscar</button></form>
    <div class="mapos-grid"><?php foreach (array_slice($items, 0, 30) as $item) { ?>
        <article class="mapos-card"><h2><a href="<?= site_url('equipamentos/visualizar/' . (int) $item->idEquipamentos) ?>">#<?= (int) $item->idEquipamentos ?> · <?= html_escape($item->equipamento) ?></a></h2>
            <p><?= html_escape($item->nomeCliente) ?></p><p><?= html_escape(trim($item->marca . ' ' . $item->modelo)) ?> · Série: <?= html_escape($item->num_serie ?: 'Não informada') ?></p>
        </article>
    <?php } ?></div>
    <?php if (! $items) { ?><p>Nenhum equipamento encontrado.</p><?php } ?>
    <?php if ($page > 0) { ?><a class="btn" href="<?= site_url('equipamentos') . '?' . html_escape(http_build_query(['q' => $query, 'pagina' => $page - 1])) ?>">Anterior</a><?php } ?>
    <?php if (count($items) > 30) { ?><a class="btn" href="<?= site_url('equipamentos') . '?' . html_escape(http_build_query(['q' => $query, 'pagina' => $page + 1])) ?>">Próxima</a><?php } ?>
</section>
