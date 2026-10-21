<?php

defined('BASEPATH') or exit('No direct script access allowed');

$ci = &get_instance();
$db = $ci->db;
$workflow = $ci->os_workflow;
$passed = 0;
$assert = function ($condition, $message) use (&$passed) {
    if (! $condition) {
        fwrite(STDERR, 'FAIL: ' . $message . "\n");
        exit(1);
    }
    $passed++;
    echo 'PASS: ' . $message . "\n";
};
$reject = function (callable $action, $message) use ($assert) {
    try {
        $action();
    } catch (DomainException $expected) {
        $assert(true, $message);

        return;
    }
    $assert(false, $message);
};
$db->insert('clientes', ['nomeCliente' => 'Cliente Teste', 'documento' => 'TEST', 'telefone' => '000', 'email' => 'client@example.test', 'senha' => password_hash('test-password', PASSWORD_DEFAULT)]);
$customer = $db->insert_id();
$db->insert('produtos', ['codDeBarra' => 'TEST', 'descricao' => 'Peça Teste', 'precoCompra' => 10, 'precoVenda' => 25, 'estoque' => 5]);
$product = $db->insert_id();
$db->insert('servicos', ['nome' => 'Serviço Teste', 'preco' => 50]);
$service = $db->insert_id();
$create = function () use ($db, $customer) {
    $db->insert('os', ['clientes_id' => $customer, 'usuarios_id' => 1, 'status' => 'Aberto', 'faturado' => 0, 'dataInicial' => date('Y-m-d'), 'dataFinal' => date('Y-m-d')]);

    return $db->insert_id();
};
$stock = function () use ($db, $product) {
    return (int) $db->get_where('produtos', ['idProdutos' => $product])->row()->estoque;
};
$order = $create();
$other = $create();
$item = ['os_id' => $order, 'produtos_id' => $product, 'quantidade' => 2, 'preco' => 25];
$itemId = $workflow->addProduct($item, true);
$assert($stock() === 3, 'Adding a product debits stock exactly once');
$workflow->changeLine($order, $itemId, 1, 25, true, true);
$assert($stock() === 4, 'Reducing a product quantity returns only the difference');
$workflow->changeLine($order, $itemId, 2, 25, true, true);
$reject(fn () => $workflow->changeLine($other, $itemId, 1, 25, true, true), 'API quantity updates cannot target another order');
$reject(fn () => $workflow->changeLine($order, $itemId, 8, 25, true, true), 'API quantity increases respect available stock');

try {
    $workflow->addProduct(array_merge($item, ['quantidade' => 1, 'nonexistent_column' => 'failure']), true);
    $assert(false, 'Database failures must abort the operation');
} catch (RuntimeException $expected) {
    $assert($stock() === 3, 'A database failure after stock debit rolls the entire transaction back');
}

$assert((float) $db->get_where('produtos_os', ['idProdutos_os' => $itemId])->row()->custo_unitario === 10.0, 'Product cost is preserved for historical reports');
$reject(fn () => $workflow->removeProduct($other, $itemId, true), 'An item from another OS cannot be removed');
$assert($stock() === 3, 'Rejected cross-order removal does not refund stock');
$reject(fn () => $workflow->addProduct(array_merge($item, ['quantidade' => 4]), true), 'Insufficient stock is rejected');
$assert($stock() === 3 && $db->where('os_id', $order)->count_all_results('produtos_os') === 1, 'Failed addition leaves stock and OS unchanged');
$reject(fn () => $workflow->addProduct(array_merge($item, ['quantidade' => '1.5']), true), 'Fractional product quantities are rejected');
$workflow->removeProduct($order, $itemId, true);
$assert($stock() === 5, 'Removing an item restores the stored quantity');
$reject(fn () => $workflow->removeProduct($order, $itemId, true), 'Repeated removal cannot duplicate refunds');
$itemId = $workflow->addProduct($item, true);
$data = ['status' => 'Cancelado', 'usuarios_id' => 1, 'dataFinal' => date('Y-m-d')];
$workflow->updateOrder($order, $data, true);
$workflow->updateOrder($order, $data, true);
$assert($stock() === 5, 'Repeated cancellation refunds stock only once');
$workflow->updateOrder($order, array_merge($data, ['status' => 'Aberto']), true);
$assert($stock() === 3, 'Reopening a cancelled OS reserves its products again');
$workflow->service($order, ['servicos_id' => $service, 'quantidade' => 1, 'preco' => 50]);
$workflow->discount($order, 'porcento', 10);
$assert((float) $db->get_where('os', ['idOs' => $order])->row()->valor_desconto === 90.0, 'Percentage discount is calculated on the server');
$reject(fn () => $workflow->discount($order, 'porcento', 110), 'Discount cannot exceed the order total');
$workflow->checklist($order, 'Teste de funcionamento');
$check = $db->get_where('os_checklist', ['os_id' => $order])->row();
$workflow->checklist($order, null, $check->id, true);
$assert((int) $db->get_where('os_checklist', ['id' => $check->id])->row()->concluido === 1, 'Checklist completion is persisted');
$reject(fn () => $workflow->checklist($other, null, $check->id, true), 'Checklist items are scoped to their OS');
$workflow->requestApproval($order);
$approval = $db->order_by('id', 'desc')->get_where('os_aprovacoes', ['os_id' => $order])->row();
$assert((float) $approval->valor === 90.0, 'Approval snapshot contains the discounted payable amount');
$reject(fn () => $workflow->answerApproval($order, $approval->id, $customer + 1, 'aprovado'), 'Another customer cannot approve the budget');
$workflow->discount($order, 'real', 20);
$reject(fn () => $workflow->answerApproval($order, $approval->id, $customer, 'aprovado'), 'Superseded budgets cannot be approved');
$workflow->requestApproval($order);
$approval = $db->order_by('id', 'desc')->get_where('os_aprovacoes', ['os_id' => $order])->row();
$workflow->answerApproval($order, $approval->id, $customer, 'aprovado');
$assert($db->get_where('os_aprovacoes', ['id' => $approval->id])->row()->status === 'aprovado', 'Customer acceptance is recorded against the snapshot');
$reject(fn () => $workflow->answerApproval($order, $approval->id, $customer, 'aprovado'), 'An approval cannot be answered twice');
$invoice = $workflow->invoice($order, ['descricao' => 'Fatura Teste', 'data_vencimento' => date('Y-m-d'), 'baixado' => 0, 'usuarios_id' => 1]);
$entry = $db->get_where('lancamentos', ['idLancamentos' => $invoice])->row();
$assert((float) $entry->valor === 100.0 && (float) $entry->valor_desconto === 80.0 && (float) $entry->desconto === 20.0, 'Invoice stores gross, discount and net amounts consistently');
$reject(fn () => $workflow->invoice($order, ['descricao' => 'Duplicada']), 'Repeated invoicing does not create another charge');
$reject(fn () => $workflow->addProduct($item, true), 'Invoiced order items cannot change');
$assert($db->where('os_id', $order)->count_all_results('os_eventos') > 5, 'Workflow writes an audit timeline');
$ci->load->library('github_updater');
$assert($ci->github_updater->has_update() === false && $ci->github_updater->update() === false, 'Browser updater cannot overwrite the fork');
$ci->load->library('permission');
$assert(! $ci->permission->checkPermission(null, 'eOs'), 'Missing permission is denied');
$ci->load->model('operacoes_model');
$summary = $ci->operacoes_model->summary(30, false);
$assert($summary['margin'] === [], 'Operational summary does not return financial data without authorization');
$financial = $ci->operacoes_model->summary(30, true);
$assert(count($financial['margin']) === 1 && (float) $financial['margin'][0]['saldo'] === 60.0, 'Financial summary uses the captured part cost and net invoice amount');
$assert((int) $financial['completed']->total === 1, 'Completed orders use an actual completion timestamp');
$assert(strpos(printSafeHtml('<p>Texto</p><script>alert(1)</script>'), '<script>') === false, 'Rich-text rendering sanitizes executable content');

$workflow->deleteOrder($order, true);
$assert($stock() === 5 && ! $db->get_where('os', ['idOs' => $order])->row(), 'Order removal and stock refund complete together');
$assert(! $db->get_where('lancamentos', ['idLancamentos' => $invoice])->row(), 'Deleting an invoiced order removes its linked financial entry');
$ci->load->model('clientes_model');
$searchPhone = $db->get_where('clientes', ['idClientes' => $customer])->row()->celular;
$db->where('idClientes', $customer)->update('clientes', ['celular' => '11912345678']);
$clientMatches = $ci->clientes_model->get('clientes', '*', '11912345678', 1, 0);
$assert(count($clientMatches) === 1 && (int) $clientMatches[0]->idClientes === (int) $customer, 'Customer search includes the mobile number used for communication');
$assert($ci->clientes_model->count('clientes', '11912345678') === 1, 'Customer pagination counts only rows matching the search');
$assert($ci->clientes_model->count('clientes', 'no-such-ui-customer') === 0 && $ci->clientes_model->get('clientes', '*', 'no-such-ui-customer', 10, 0) === [], 'An empty customer search has no unrelated rows or phantom pages');
$db->where('idClientes', $customer)->update('clientes', ['celular' => $searchPhone]);
require __DIR__ . '/communication.php';
require __DIR__ . '/equipment.php';
require __DIR__ . '/inspections.php';
require __DIR__ . '/kanban.php';
require __DIR__ . '/order_lifecycle.php';
require __DIR__ . '/budget_whatsapp.php';
try {
    $ci->migration->version(20261020180000);
    $assert(false, 'Migration rollback must preserve existing exit evidence');
} catch (RuntimeException $expected) {
    $assert($db->where('tipo', 'saida')->count_all_results('os_vistorias') > 0, 'Migration refuses to discard existing delivery inspections');
}
// This suite only runs against the named disposable test database.
foreach ($db->where('tipo', 'saida')->get('os_vistorias')->result() as $exitInspection) {
    $db->where('vistoria_id', $exitInspection->id)->delete('os_vistoria_fotos');
}
$db->where('tipo', 'saida')->delete('os_vistorias');
$assert($ci->migration->version(20240503170400) && ! $db->table_exists('os_eventos') && ! $db->table_exists('com_fila'), 'Workflow and communication migrations roll back in the disposable database');
$assert($ci->migration->latest() && $db->table_exists('os_eventos'), 'Workflow migration can be reapplied');
echo "Completed: $passed integration assertions.\n";
