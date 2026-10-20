<main class="mapos-operations mapos-kanban"><div class="mapos-section-heading"><div><span class="mapos-eyebrow">FLUXO DOS ATENDIMENTOS</span><h1>Kanban de ordens de serviço</h1><p>Veja o que precisa avançar, quem está responsável e quais prazos exigem atenção.</p></div><a class="btn" href="<?= site_url('os') ?>">Ver lista de OS</a></div>
    <form method="get" class="mapos-kanban-filters"><label>Cliente, equipamento, série ou número da OS<input name="q" maxlength="100" value="<?= html_escape($query) ?>"></label>
        <label>Técnico<select name="tecnico"><option value="0">Todos</option><?php foreach ($technicians as $person) { ?><option value="<?= (int) $person->idUsuarios ?>" <?= $technician === (int) $person->idUsuarios ? 'selected' : '' ?>><?= html_escape($person->nome) ?></option><?php } ?></select></label>
        <label><input name="encerradas" type="checkbox" value="1" <?= $closed ? 'checked' : '' ?>> Incluir entregues e canceladas recentes</label><button class="btn btn-primary">Filtrar</button>
    </form><p><?= (int) $board['total'] ?> atendimentos neste quadro. Entregues: últimos 30 dias; canceladas: abertas nos últimos 30 dias. <?= $canMove ? 'Use o seletor de etapa ou arraste os cartões para as colunas de destino.' : '' ?></p>
    <p>Aguardando aprovação reúne orçamentos com resposta pendente; solicite a aprovação nos detalhes da OS. Mover para Autorizadas registra uma decisão da equipe e não substitui a resposta do cliente.</p>
    <?php if ($board['limited']) { ?><p class="alert">O quadro mostra até 500 OS. Use os filtros para reduzir a seleção.</p><?php } ?>
    <div class="mapos-kanban-board"><?php foreach ($board['columns'] as $key => $orders) { ?><section class="mapos-kanban-column" data-kanban-target="<?= html_escape(Kanban_model::TARGETS[$key] ?? '') ?>" aria-label="<?= html_escape(Kanban_model::COLUMNS[$key]) ?>">
        <h2><?= html_escape(Kanban_model::COLUMNS[$key]) ?> <span class="mapos-pill"><?= count($orders) ?></span></h2>
        <?php foreach ($orders as $order) {
            $movable = $canMove && ! $order->faturado && ! $order->entregue_em && ! in_array($order->status, ['Cancelado', 'Faturado'], true); ?>
            <article class="mapos-kanban-card <?= $order->atrasada ? 'is-late' : '' ?>" draggable="<?= $movable ? 'true' : 'false' ?>" data-kanban-card="<?= (int) $order->idOs ?>">
                <h3><a href="<?= site_url('os/visualizar/' . (int) $order->idOs) ?>">OS #<?= (int) $order->idOs ?></a></h3><strong><?= html_escape($order->nomeCliente) ?></strong>
                <p><?= html_escape($order->equipamentos ?: 'Equipamento sem cadastro vinculado') ?></p><p>Técnico: <?= html_escape($order->nome) ?></p>
                <p>Previsão: <?= $order->dataFinal ? html_escape(date('d/m/Y', strtotime($order->dataFinal))) : 'Não definida' ?><?= $order->atrasada ? ' · Prazo vencido' : '' ?></p><span class="mapos-pill"><?= html_escape($order->status) ?></span>
                <?php if ($order->aprovacao_pendente) { ?><p>Resposta do orçamento pendente</p><?php } ?>
                <?php if ($order->entregue_em) { ?><p>Entrega: <?= html_escape(date('d/m/Y H:i', strtotime($order->entregue_em))) ?></p><?php } ?>
                <?php if ($movable) { ?>
                    <?= form_open('kanban/mover/' . (int) $order->idOs, ['data-kanban-form' => '1']) ?><input type="hidden" name="status_anterior" value="<?= html_escape($order->status) ?>"><input type="hidden" name="q" value="<?= html_escape($query) ?>"><input type="hidden" name="tecnico" value="<?= (int) $technician ?>"><input type="hidden" name="encerradas" value="<?= $closed ? '1' : '0' ?>">
                        <label for="kanban-status-<?= (int) $order->idOs ?>">Mover para</label><select id="kanban-status-<?= (int) $order->idOs ?>" name="status"><?php foreach (['Aberto', 'Orçamento', 'Negociação', 'Aprovado', 'Aguardando Peças', 'Em Andamento', 'Finalizado', 'Cancelado'] as $status) { ?><option value="<?= html_escape($status) ?>" <?= $order->status === $status ? 'selected' : '' ?>><?= html_escape($status) ?></option><?php } ?></select><button class="btn btn-small">Atualizar etapa</button>
                    </form>
                <?php } ?>
            </article>
        <?php } ?>
        <?php if (! $orders) { ?><p class="mapos-muted">Nenhuma OS nesta etapa.</p><?php } ?>
    </section><?php } ?></div>
</main><script src="<?= base_url('assets/js/kanban.js') ?>" defer></script>
