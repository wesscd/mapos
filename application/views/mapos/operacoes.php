<main class="mapos-operations">
    <div class="mapos-section-heading"><div><span class="mapos-eyebrow">VISÃO DA OPERAÇÃO</span><h1>Atendimentos e resultados</h1><p>Priorize os prazos e acompanhe a entrega da equipe.</p></div>
        <form method="get"><label for="periodo-operacoes">Conclusões no período</label><select id="periodo-operacoes" name="dias"><option value="7" <?= $operations['days'] === 7 ? 'selected' : '' ?>>Últimos 7 dias</option><option value="30" <?= $operations['days'] === 30 ? 'selected' : '' ?>>Últimos 30 dias</option><option value="90" <?= $operations['days'] === 90 ? 'selected' : '' ?>>Últimos 90 dias</option></select> <button class="btn" type="submit">Aplicar</button></form>
    </div>
    <div class="mapos-metrics">
        <article class="mapos-card"><span>OS em aberto</span><strong><?= (int) $operations['backlog'] ?></strong><small>Todos os atendimentos ativos</small></article>
        <article class="mapos-card"><span>Prazo vencido</span><strong><?= count($operations['late']) ?></strong><small>Data prevista anterior a hoje</small></article>
        <article class="mapos-card"><span>Concluídas no período</span><strong><?= (int) $operations['completed']->total ?></strong><small>Finalizadas ou faturadas</small></article>
        <article class="mapos-card"><span>Tempo médio de atendimento</span><strong><?= $operations['completed']->dias !== null ? html_escape(number_format($operations['completed']->dias, 1, ',', '.')) . ' dias' : '—' ?></strong><small>Da abertura à conclusão registrada</small></article>
    </div>
    <div class="mapos-grid">
        <section class="mapos-card"><h2>Precisam de atenção</h2><p class="mapos-muted">OS com prazo vencido, das mais antigas para as mais recentes.</p>
            <?php if (! $operations['late']) { ?><p>Nenhuma OS atrasada. Os prazos estão em dia.</p><?php } ?>
            <div class="mapos-table-scroll"><table class="table"><thead><tr><th>OS</th><th>Cliente</th><th>Responsável</th><th>Prazo</th></tr></thead><tbody>
            <?php foreach ($operations['late'] as $order) { ?><tr><td><a href="<?= site_url('os/visualizar/' . (int) $order->idOs) ?>">#<?= (int) $order->idOs ?></a></td><td><?= html_escape($order->nomeCliente) ?></td><td><?= html_escape($order->nome) ?></td><td><?= html_escape(date('d/m/Y', strtotime($order->dataFinal))) ?></td></tr><?php } ?>
            </tbody></table></div>
        </section>
        <section class="mapos-card"><h2>Entregas por responsável</h2><p class="mapos-muted">Conclusões registradas no período selecionado.</p>
            <?php if (! $operations['technicians']) { ?><p>Ainda não há conclusões registradas no período.</p><?php } ?>
            <ul class="mapos-ranking"><?php foreach ($operations['technicians'] as $technician) { ?><li><span><?= html_escape($technician->nome) ?></span><strong><?= (int) $technician->total ?> OS</strong></li><?php } ?></ul>
        </section>
    </div>
    <?php if ($canFinance) { ?>
    <section class="mapos-card"><h2>Receita e custo das peças por OS</h2><p>OS faturadas no período. O saldo considera a receita líquida menos o custo das peças registrado ao adicioná-las. Mão de obra, impostos e despesas operacionais não estão incluídos.</p>
        <div class="mapos-table-scroll"><table class="table"><thead><tr><th>OS</th><th>Receita líquida</th><th>Custo das peças</th><th>Saldo estimado</th></tr></thead><tbody>
        <?php foreach ($operations['margin'] as $row) { ?><tr><td><a href="<?= site_url('os/visualizar/' . (int) $row['id']) ?>">#<?= (int) $row['id'] ?></a></td><td>R$ <?= html_escape(number_format($row['receita'], 2, ',', '.')) ?></td><td><?= $row['faltante'] ? 'Custo incompleto' : 'R$ ' . html_escape(number_format($row['custo'], 2, ',', '.')) ?></td><td><?= $row['faltante'] ? '—' : 'R$ ' . html_escape(number_format($row['saldo'], 2, ',', '.')) ?></td></tr><?php } ?>
        </tbody></table></div>
    </section>
    <?php } ?>
    <p class="mapos-muted">As datas reais de conclusão e os custos históricos começam a ser registrados nesta atualização; dados antigos não são estimados retroativamente.</p>
</main>
