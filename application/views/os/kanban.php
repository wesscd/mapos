<main class="ui-page ui-kanban mapos-kanban"><header class="ui-page-header"><div><span class="ui-eyebrow">FLUXO DOS ATENDIMENTOS</span><h1>Kanban de ordens de serviço</h1><p>Veja o que precisa avançar, quem está responsável e em qual etapa está cada atendimento.</p></div><a class="ui-button" href="<?= site_url('os') ?>">Ver lista de OS</a></header>
    <div class="ui-card">
    <form method="get" class="ui-search ui-kanban-filters" action="<?= site_url('kanban') ?>" role="search"><div><label for="kanban-search">Cliente, equipamento, série ou número da OS</label><input id="kanban-search" name="q" type="search" maxlength="100" value="<?= html_escape($query) ?>"></div>
        <div><label for="kanban-technician">Responsável</label><select id="kanban-technician" name="tecnico"><option value="0">Todos</option><?php foreach ($technicians as $person) { ?><option value="<?= (int) $person->idUsuarios ?>" <?= $technician === (int) $person->idUsuarios ? 'selected' : '' ?>><?= html_escape($person->nome) ?></option><?php } ?></select></div>
        <div class="ui-checkbox-row"><label for="kanban-closed"><input id="kanban-closed" name="encerradas" type="checkbox" value="1" <?= $closed ? 'checked' : '' ?>> Incluir entregues e canceladas recentes</label></div><button type="submit" class="ui-button ui-button-primary">Filtrar</button><a class="ui-button" href="<?= site_url('kanban') ?>">Limpar filtros</a>
    </form><p><?= (int) $board['total'] ?> atendimentos neste quadro. Entregues: últimos 30 dias; canceladas: abertas nos últimos 30 dias. <?= $canMove ? 'Use o seletor de etapa ou arraste os cartões para as colunas de destino.' : '' ?></p>
    <details class="ui-disclosure ui-kanban-help"><summary>Como avançar os atendimentos</summary><p>Aguardando aprovação reúne orçamentos com resposta pendente; solicite a aprovação nos detalhes da OS. Mover para Autorizadas registra uma decisão da equipe e não substitui a resposta do cliente.</p></details>
    <?php if ($board['limited']) { ?><p class="ui-notice">O quadro mostra até 500 OS. Use os filtros para reduzir a seleção.</p><?php } ?>
    <div class="ui-kanban-jump"><label for="kanban-jump">Ir para etapa</label><select id="kanban-jump"><option value="">Selecione uma etapa</option><?php foreach ($board['columns'] as $jumpKey => $jumpOrders) { ?><option value="kanban-column-<?= html_escape($jumpKey) ?>"><?= html_escape(Kanban_model::COLUMNS[$jumpKey]) ?></option><?php } ?></select></div></div>
    <p class="ui-hint">Deslize o quadro para ver as demais etapas. No celular, use “Ir para etapa”.</p>
    <div class="mapos-kanban-board" role="region" aria-label="Quadro de ordens por etapa" tabindex="0"><?php foreach ($board['columns'] as $key => $orders) { ?><section class="mapos-kanban-column" id="kanban-column-<?= html_escape($key) ?>" data-kanban-target="<?= html_escape(Kanban_model::TARGETS[$key] ?? '') ?>" aria-label="<?= html_escape(Kanban_model::COLUMNS[$key]) ?>">
        <h2><?= html_escape(Kanban_model::COLUMNS[$key]) ?> <span class="ui-badge"><?= count($orders) ?></span></h2>
        <?php foreach ($orders as $order) {
            $movable = $canMove && ! $order->faturado && ! $order->entregue_em && ! in_array($order->status, ['Cancelado', 'Faturado'], true); ?>
            <article class="mapos-kanban-card" draggable="<?= $movable ? 'true' : 'false' ?>" data-kanban-card="<?= (int) $order->idOs ?>">
                <h3><a href="<?= site_url('os/visualizar/' . (int) $order->idOs) ?>">OS #<?= (int) $order->idOs ?></a></h3><strong><?= html_escape($order->nomeCliente) ?></strong>
                <p><?= html_escape($order->equipamentos ?: 'Equipamento sem cadastro vinculado') ?></p><p>Responsável: <?= html_escape($order->nome) ?></p>
                <span class="ui-badge"><?= html_escape($order->status) ?></span>
                <?php if ($order->aprovacao_pendente) { ?><p>Resposta do orçamento pendente</p><?php } ?>
                <?php if ($order->entregue_em) { ?><p>Entrega: <?= html_escape(date('d/m/Y H:i', strtotime($order->entregue_em))) ?></p><?php } ?>
                <?php if ($movable) { ?>
                    <?= form_open('kanban/mover/' . (int) $order->idOs, ['data-kanban-form' => '1']) ?><input type="hidden" name="status_anterior" value="<?= html_escape($order->status) ?>"><input type="hidden" name="q" value="<?= html_escape($query) ?>"><input type="hidden" name="tecnico" value="<?= (int) $technician ?>"><input type="hidden" name="encerradas" value="<?= $closed ? '1' : '0' ?>">
                        <label for="kanban-status-<?= (int) $order->idOs ?>">Mover para</label><select id="kanban-status-<?= (int) $order->idOs ?>" name="status"><?php foreach (['Aberto', 'Orçamento', 'Negociação', 'Aprovado', 'Aguardando Peças', 'Em Andamento', 'Finalizado', 'Cancelado'] as $status) { ?><option value="<?= html_escape($status) ?>" <?= $order->status === $status ? 'selected' : '' ?>><?= html_escape($status) ?></option><?php } ?></select><button class="ui-button ui-button-small">Atualizar etapa</button>
                    </form>
                <?php } ?>
            </article>
        <?php } ?>
        <?php if (! $orders) { ?><p class="mapos-muted">Nenhuma OS nesta etapa.</p><?php } ?>
    </section><?php } ?></div>
</main><script src="<?= base_url('assets/js/kanban.js') ?>?v=2" defer></script>
