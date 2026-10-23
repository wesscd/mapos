<?php

defined('BASEPATH') or exit('No direct script access allowed');

$ci->load->library('checklist_service');
$checks = $ci->checklist_service;
$ci->session->set_userdata('id_admin', 1);
$checkOrder = $create();
$checkEquipment = $equipmentService->save(array_merge($equipmentData, ['equipamento' => 'Notebook checklist teste', 'checklist_tipo' => 'notebook']));
$equipmentService->link($checkOrder, $checkEquipment);
$groups = $checks->details($checkOrder);
$assert(count($groups) === 1 && $groups[0]->tipo === 'notebook', 'Linking typed equipment automatically copies the active checklist into its OS');
$group = $groups[0];
$assert(count(array_filter($group->itens, fn ($item) => $item->fase === 'analise')) > 0 && count(array_filter($group->itens, fn ($item) => $item->fase === 'execucao')) > 0, 'Equipment checklist has separate analysis and execution items');
$count = $db->count_all('os_check_itens');
$assert($checks->apply($checkOrder, 'desktop', $checkEquipment) === (int) $group->id && $db->count_all('os_check_itens') === $count, 'Repeated application preserves the original snapshot without duplicate items');
$reject(fn () => $checks->apply($checkOrder, 'notebook', $equipmentId), 'Checklist cannot target equipment not linked to the order');
$reject(fn () => $checks->apply($checkOrder, ['bad']), 'Checklist model rejects malformed equipment types');
$model = $db->get_where('check_modelos', ['tipo' => 'notebook'])->row();
$oldItems = json_decode($model->itens, true);
$newItems = $oldItems;
$newItems[0]['descricao'] = 'Verificação nova para futuras OS';
$checks->saveModel($model->id, ['versao' => $model->versao, 'nome' => $model->nome, 'ativo' => 1, 'itens' => $newItems]);
$assert($checks->details($checkOrder)[0]->itens[0]->descricao === $oldItems[0]['descricao'], 'Editing a model never rewrites an existing OS checklist');
$reject(fn () => $checks->saveModel($model->id, ['versao' => $model->versao, 'nome' => 'stale', 'itens' => $oldItems]), 'Concurrent model edits require refreshing the version');
$nextCheckOrder = $create();
$equipmentService->link($nextCheckOrder, $checkEquipment);
$assert($checks->details($nextCheckOrder)[0]->itens[0]->descricao === $newItems[0]['descricao'], 'New equipment links copy the latest model revision');
$checks->saveModel($model->id, ['versao' => $model->versao + 1, 'nome' => $model->nome, 'ativo' => 1, 'itens' => $oldItems]);
$checks->saveModel($model->id, ['versao' => $model->versao + 2, 'nome' => $model->nome, 'ativo' => 0, 'itens' => $oldItems]);
$disabledOrder = $create();
$equipmentService->link($disabledOrder, $checkEquipment);
$assert($checks->details($disabledOrder) === [], 'Disabled template does not block equipment linking or silently create a checklist');
$reject(fn () => $checks->apply($disabledOrder, 'notebook', $checkEquipment), 'Disabled templates cannot be applied manually');
$checks->saveModel($model->id, ['versao' => $model->versao + 3, 'nome' => $model->nome, 'ativo' => 1, 'itens' => $oldItems]);
$analysis = current(array_filter($group->itens, fn ($item) => $item->fase === 'analise'));
$intervention = current(array_filter($group->itens, fn ($item) => $item->fase === 'execucao' && $item->escopo === 'autorizacao'));
$reject(fn () => $checks->update($checkOrder, $analysis->id, ['versao' => 1, 'resultado' => 'atencao']), 'Analysis attention requires an observation');
$checks->update($checkOrder, $analysis->id, ['versao' => 1, 'resultado' => 'conforme', 'observacoes' => 'Teste observado']);
$saved = $db->get_where('os_check_itens', ['id' => $analysis->id])->row();
$assert($saved->resultado === 'conforme' && (int) $saved->atualizado_por === 1 && $saved->updated_at, 'Checklist outcomes record responsible user and timestamp');
$auditEntry = $db->get_where('os_check_registros', ['item_id' => $analysis->id])->row();
$assert(json_decode($auditEntry->dados, true)['depois']['observacoes'] === 'Teste observado', 'Checklist audit preserves the previous and new observations independently of the current result');
$reject(fn () => $checks->update($nextCheckOrder, $analysis->id, ['versao' => 2, 'resultado' => 'conforme']), 'Checklist items cannot be updated through another OS');
$reject(fn () => $checks->update($checkOrder, $analysis->id, ['versao' => 1, 'resultado' => 'conforme']), 'Concurrent item updates cannot overwrite another technician result');
$reject(fn () => $checks->update($checkOrder, $analysis->id, ['versao' => 2, 'resultado' => 'realizado']), 'Analysis cannot accept execution-only outcomes');
$reject(fn () => $checks->update($checkOrder, $intervention->id, ['versao' => 1, 'resultado' => 'realizado']), 'An intervention requires an authorization reference before it can be marked done');
$beforeTotals = [$db->count_all('servicos_os'), $db->count_all('produtos_os'), $db->count_all('lancamentos')];
$checks->update($checkOrder, $intervention->id, ['versao' => 1, 'resultado' => 'realizado', 'observacoes' => 'Nota interna reservada', 'autorizacao' => 'Referência interna de autorização']);
$assert($beforeTotals === [$db->count_all('servicos_os'), $db->count_all('produtos_os'), $db->count_all('lancamentos')], 'Completing technical work never creates billable services, products or charges');
$reject(fn () => $checks->update($checkOrder, $analysis->id, ['versao' => 2, 'resultado' => 'atencao', 'observacoes' => 'Teste inválido'], '<svg onload="alert(1)"/>'), 'Invalid checklist photo content is rejected atomically');
$assert($db->get_where('os_check_itens', ['id' => $analysis->id])->row()->resultado === 'conforme', 'A rejected photo leaves the previous checklist result unchanged');
$checks->update($checkOrder, $analysis->id, ['versao' => 2, 'resultado' => 'conforme'], $bytes);
$assert($db->where('item_id', $analysis->id)->count_all_results('os_check_fotos') === 1, 'Checklist photo is normalized and stored privately with its item');
$checks->update($checkOrder, $analysis->id, ['versao' => 3, 'resultado' => 'conforme'], $bytes);
$checks->update($checkOrder, $analysis->id, ['versao' => 4, 'resultado' => 'conforme'], $bytes);
$reject(fn () => $checks->update($checkOrder, $analysis->id, ['versao' => 5, 'resultado' => 'conforme'], $bytes), 'Checklist photo limit preserves the three existing evidence images');
$checks->add($checkOrder, $group->id, ['fase' => 'execucao', 'descricao' => 'Teste adicional deste atendimento', 'escopo' => 'complementar']);
$assert(count($checks->details($checkOrder)[0]->itens) === count($group->itens) + 1, 'Order-specific procedures do not change the reusable template');
$reject(fn () => $checks->add($nextCheckOrder, $group->id, ['fase' => 'analise', 'descricao' => 'Wrong OS', 'escopo' => 'verificacao']), 'New checklist items are scoped to the owning OS');
$html = $ci->load->view('checklists/resumo', ['order' => $db->get_where('os', ['idOs' => $checkOrder])->row(), 'groups' => $checks->details($checkOrder)], true);
$assert(str_contains($html, $intervention->descricao) && ! str_contains($html, 'Nota interna reservada') && ! str_contains($html, 'Referência interna de autorização'), 'Client procedure summary includes completed execution without internal observations or authorization notes');
$reject(fn () => $workflow->deleteOrder($checkOrder, false), 'OS with a technical checklist preserves its historical records');
$db->where('idOs', $checkOrder)->update('os', ['entregue_em' => date('Y-m-d H:i:s')]);
$reject(fn () => $checks->update($checkOrder, $analysis->id, ['versao' => 5, 'resultado' => 'conforme']), 'Delivered OS locks checklist results and evidence');
$db->where('idOs', $nextCheckOrder)->update('os', ['faturado' => 1]);
$reject(fn () => $checks->add($nextCheckOrder, $checks->details($nextCheckOrder)[0]->id, ['fase' => 'analise', 'descricao' => 'Locked', 'escopo' => 'verificacao']), 'Invoiced OS cannot acquire new checklist work');
try {
    $ci->migration->version(20261021100000);
    $assert(false, 'Checklist migration must refuse to discard history');
} catch (RuntimeException $expected) {
    $assert($db->count_all('os_check_grupos') > 0, 'Migration rollback refuses to discard OS checklist snapshots');
}
// Remove only the synthetic checklist fixtures to permit this suite's migration rollback audit.
$db->empty_table('os_check_fotos');
$db->empty_table('os_check_registros');
$db->empty_table('os_check_itens');
$db->empty_table('os_check_grupos');
