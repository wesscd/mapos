<?php

defined('BASEPATH') or exit('No direct script access allowed');

$ci->load->model('kanban_model');
$boardOrder = $workflow->createOrder(array_merge($newOrderData, ['dataInicial' => date('Y-m-d'), 'dataFinal' => date('Y-m-d', strtotime('-1 day'))]));
$columnIds = fn ($board, $key) => array_map(fn ($row) => (int) $row->idOs, $board['columns'][$key] ?? []);
$board = $ci->kanban_model->board();
$assert(in_array($boardOrder, $columnIds($board, 'abertas'), true), 'Kanban includes newly opened orders in the correct stage');
$card = array_values(array_filter($board['columns']['abertas'], fn ($row) => (int) $row->idOs === $boardOrder))[0];
$assert($card->atrasada && ! isset($card->valorTotal), 'Kanban marks overdue work without querying financial amounts');
$assert($ci->kanban_model->board('', 999999)['total'] === 0, 'Kanban technician filter excludes other technicians');
$workflow->transition($boardOrder, 'Em Andamento', 'Aberto', false);
$assert(in_array($boardOrder, $columnIds($ci->kanban_model->board(), 'execucao'), true), 'Kanban transition updates the real service stage');
$reject(fn () => $workflow->transition($boardOrder, 'Finalizado', 'Aberto', false), 'A stale Kanban card cannot overwrite a concurrent stage change');
$reject(fn () => $workflow->transition($boardOrder, 'Faturado', 'Em Andamento', false), 'Kanban does not bypass the invoice transaction');
$workflow->transition($boardOrder, 'Orçamento', 'Em Andamento', false);
$workflow->service($boardOrder, ['servicos_id' => $service, 'quantidade' => 1, 'preco' => 25]);
$workflow->requestApproval($boardOrder);
$assert(in_array($boardOrder, $columnIds($ci->kanban_model->board(), 'aprovacao'), true), 'Pending customer budget decisions have their own Kanban column');
$workflow->transition($boardOrder, 'Finalizado', 'Orçamento', false);
$assert(in_array($boardOrder, $columnIds($ci->kanban_model->board(), 'retirada'), true), 'Completed service remains on the board until physical delivery');
$workflow->deliver($boardOrder);
$assert(! in_array($boardOrder, $columnIds($ci->kanban_model->board(), 'retirada'), true)
    && in_array($boardOrder, $columnIds($ci->kanban_model->board('', 0, true), 'entregues'), true), 'Delivery moves the OS out of active work and into optional recent deliveries');
$reject(fn () => $workflow->transition($boardOrder, 'Aberto', 'Finalizado', false), 'Kanban cannot reopen a delivered order');
$stockBeforeBoard = $stock();
$cancelOrder = $workflow->createOrder(array_merge($newOrderData, ['dataInicial' => date('Y-m-d')]));
$workflow->addProduct(['os_id' => $cancelOrder, 'produtos_id' => $product, 'quantidade' => 1, 'preco' => 25], true);
$workflow->transition($cancelOrder, 'Cancelado', 'Aberto', true);
$reject(fn () => $workflow->transition($cancelOrder, 'Cancelado', 'Aberto', true), 'Repeated Kanban cancellations are rejected as stale');
$assert($stock() === $stockBeforeBoard && in_array($cancelOrder, $columnIds($ci->kanban_model->board('', 0, true), 'canceladas'), true), 'Kanban cancellation refunds reserved stock exactly once and preserves cancelled work');
