<?php

defined('BASEPATH') or exit('No direct script access allowed');

$ci->load->library('comunicacao_service');
$communication = $ci->comunicacao_service;
$settings = ['whatsapp' => 1, 'email' => 1, 'inicio' => 0, 'fim' => 24, 'intervalo_dias' => 1, 'limite_lembretes' => 2];
$communication->saveConfig($settings);
$assert($communication->destination('whatsapp', (object) ['celular' => '+1 202 555 0123', 'telefone' => '']) === '12025550123', 'Explicit international country prefixes are preserved');
$db->where('idClientes', $customer)->update('clientes', ['celular' => '(11) 99999-9999']);
$id = $workflow->createOrder(['clientes_id' => $customer, 'usuarios_id' => 1, 'status' => 'Aberto', 'faturado' => 0, 'dataInicial' => date('Y-m-d'), 'dataFinal' => date('Y-m-d')]);
$assert($db->where('os_id', $id)->count_all_results('com_fila') === 0, 'No automatic notification without explicit customer preference');
$reject(fn () => $communication->savePreference($customer, 'whatsapp', '', 'test'), 'Opt-in requires recorded evidence');
$communication->savePreference($customer, 'ambos', 'Autorização de teste', 'test');
$id = $workflow->createOrder(['clientes_id' => $customer, 'usuarios_id' => 1, 'status' => 'Aberto', 'faturado' => 0, 'dataInicial' => date('Y-m-d'), 'dataFinal' => date('Y-m-d')]);
$assert($db->where('os_id', $id)->count_all_results('com_fila') === 2, 'Creating an order transactionally enqueues one message per authorized channel');
$job = $db->get_where('com_fila', ['os_id' => $id, 'canal' => 'whatsapp'])->row();
$assert($job->destino === '5511999999999' && str_contains($job->texto, 'mine/visualizarOs/' . $id), 'Phone normalization and authenticated customer portal link are preserved');
$communication->capture($id, 'status', 'same-source');
$communication->capture($id, 'status', 'same-source');
$assert($db->where('os_id', $id)->count_all_results('com_fila') === 4, 'Repeated event source cannot enqueue duplicates');
require __DIR__ . '/customer_messages.php';
$communication->savePreference($customer, 'nenhum', '', 'test');
$assert(! $communication->allowed($job), 'Opt-out revokes queued messages before sending');
$communication->savePreference($customer, 'ambos', 'Autorização de teste', 'test');
$db->where('idClientes', $customer)->update('clientes', ['celular' => '11988888888']);
$assert(! $communication->allowed($job), 'Changing contact information invalidates old queued destinations');
$db->where('idClientes', $customer)->update('clientes', ['celular' => '11999999999']);
$reject(fn () => $communication->saveConfig(array_merge($settings, ['fim' => 0])), 'Invalid sending windows are rejected');
$model = $db->get_where('com_modelos', ['canal' => 'whatsapp'])->row();
$reject(fn () => $communication->saveTemplate($model->id, 'Unknown {password}', true), 'Templates reject unknown substitution fields');

$before = $db->count_all('os');
$db->query('RENAME TABLE com_fila TO com_fila_failure');
try {
    $workflow->createOrder(['clientes_id' => $customer, 'usuarios_id' => 1, 'status' => 'Aberto', 'faturado' => 0]);
    $assert(false, 'Outbox database failure must abort OS creation');
} catch (Throwable $expected) {
    $assert($db->count_all('os') === $before, 'Order creation rolls back when its notification cannot be recorded');
} finally {
    $db->query('RENAME TABLE com_fila_failure TO com_fila');
}
$workflow->service($id, ['servicos_id' => $service, 'quantidade' => 1, 'preco' => 50]);
$workflow->requestApproval($id);
$budget = $db->order_by('id', 'desc')->get_where('com_fila', ['os_id' => $id, 'evento' => 'orcamento'])->row();
$workflow->discount($id, 'real', 5);
$assert(! $communication->allowed($budget), 'Superseded approval notifications are cancelled before sending');
$workflow->requestApproval($id);
$approval = $db->order_by('id', 'desc')->get_where('os_aprovacoes', ['os_id' => $id])->row();
$db->where('id', $approval->id)->update('os_aprovacoes', ['created_at' => date('Y-m-d H:i:s', time() - 3 * 86400)]);
$communication->reminders();
$communication->reminders();
$assert($db->where('os_id', $id)->where('evento', 'lembrete_orcamento')->count_all_results('com_fila') === 2, 'Overdue reminders enqueue at most one slot per channel, without a catch-up burst');
$workflow->updateOrder($id, ['status' => 'Finalizado', 'usuarios_id' => 1, 'dataFinal' => date('Y-m-d')], true);
$pickup = $db->get_where('com_fila', ['os_id' => $id, 'evento' => 'pronto'])->row();
$assert($pickup && $communication->allowed($pickup), 'Completing a service enqueues the ready-for-pickup notification');
$workflow->deliver($id);
$assert(! $communication->allowed($pickup) && $db->get_where('os', ['idOs' => $id])->row()->entregue_em, 'Recorded delivery stops outstanding pickup notifications');
$reject(fn () => $workflow->deliver($id), 'Repeated delivery cannot enqueue duplicate notifications');

$db->where('status', 'pendente')->update('com_fila', ['status' => 'cancelado']);
$communication->testMessage('whatsapp', '5511999999999');
$sent = 0;
$communication->process(function ($job) use (&$sent) {
    $sent++;
    return ['state' => 'enviado', 'message' => 'Fake transport accepted'];
});
$communication->process(function ($job) use (&$sent) {
    $sent++;
    return ['state' => 'enviado', 'message' => 'Fake transport accepted'];
});
$assert($sent === 1, 'A completed message is not resent when the worker runs again');
$communication->testMessage('email', 'controlled@example.test');
$communication->process(fn ($job) => ['state' => 'incerto', 'message' => 'Ambiguous timeout']);
$communication->process(function ($job) use (&$sent) {
    $sent++;
    return ['state' => 'enviado', 'message' => 'Fake transport'];
});
$assert($sent === 1 && $db->where('status', 'incerto')->count_all_results('com_fila') === 1, 'Ambiguous sends are never automatically retried');
$uncertain = $db->get_where('com_fila', ['status' => 'incerto'])->row();
$reject(fn () => $communication->manageJob($uncertain->id, 'repetir'), 'Administrator cannot blindly retry an uncertain delivery');
$assert($db->count_all('com_tentativas') >= 2, 'Sending attempts are recorded without transport credentials');
$db->where('status', 'pendente')->update('com_fila', ['status' => 'cancelado']);
$communication->capture($id, 'status', 'paused-window');
$hour = (int) date('G');
$communication->saveConfig(array_merge($settings, ['inicio' => $hour === 0 ? 1 : 0, 'fim' => $hour === 0 ? 2 : $hour]));
$communication->process(function ($job) use (&$sent) {
    $sent++;
    return ['state' => 'enviado', 'message' => 'Should not run'];
});
$assert($sent === 1, 'Automatic messages wait outside the configured sending window');
$communication->saveConfig($settings);
$db->where('status', 'pendente')->update('com_fila', ['status' => 'cancelado']);
$communication->testMessage('whatsapp', '5511999999999');
$communication->process(fn ($job) => ['state' => 'retry', 'message' => 'Disconnected before send']);
$retry = $db->order_by('id', 'desc')->get_where('com_fila', ['evento' => 'teste'])->row();
$assert($retry->status === 'pendente' && $retry->tentativas == 1 && strtotime($retry->agendado_em) > time(), 'Safe failures are delayed before a bounded retry');
$db->where('id', $retry->id)->update('com_fila', ['tentativas' => 4, 'agendado_em' => date('Y-m-d H:i:s')]);
$communication->process(fn ($job) => ['state' => 'retry', 'message' => 'Still disconnected']);
$assert($db->get_where('com_fila', ['id' => $retry->id])->row()->status === 'falhou', 'Five safe failures stop automatic retry attempts');
$db->where('id', $retry->id)->update('com_fila', ['status' => 'enviando', 'updated_at' => date('Y-m-d H:i:s', time() - 600)]);
$communication->process(fn ($job) => ['state' => 'enviado', 'message' => 'Should not run']);
$assert($db->get_where('com_fila', ['id' => $retry->id])->row()->status === 'incerto', 'An interrupted worker marks an old send uncertain instead of repeating it');
$communication->testMessage('whatsapp', '5511999999999');
$communication->process(fn ($job) => ['state' => 'throttle', 'message' => 'Rate limit']);
$throttled = $db->order_by('id', 'desc')->get_where('com_fila', ['evento' => 'teste'])->row();
$assert($throttled->status === 'pendente' && (int) $throttled->tentativas === 0, 'Rate limiting postpones messages without exhausting their retry budget');
$communication->manageJob($throttled->id, 'cancelar');
$communication->saveConfig(array_merge($settings, ['whatsapp' => 0, 'email' => 0]));
$workflow->deleteOrder($id, true);
