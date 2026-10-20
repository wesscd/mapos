<?php

defined('BASEPATH') or exit('No direct script access allowed');

$savedMessage = $db->get_where('com_fila', ['id' => $job->id])->row()->texto;
$assert($ci->migration->version(20261019210000), 'Message copy migration can be reversed independently');
$original = $db->get_where('com_modelos', ['evento' => 'abertura', 'canal' => 'whatsapp'])->row();
$custom = lcfirst($original->texto);
$db->where('id', $original->id)->update('com_modelos', ['texto' => $custom, 'ativo' => 0]);
$assert($ci->migration->latest(), 'Revised customer messages migrate successfully');
$preserved = $db->get_where('com_modelos', ['id' => $original->id])->row();
$assert($preserved->texto === $custom && (int) $preserved->ativo === 0, 'Migration preserves case-only customizations and disabled models');
$updated = $db->get_where('com_modelos', ['evento' => 'abertura', 'canal' => 'email'])->row();
$assert(str_contains($updated->texto, 'solicitação de atendimento') && ! str_contains($updated->texto, 'Recebemos'), 'Opening copy confirms registration without asserting physical receipt');
$assert($db->get_where('com_fila', ['id' => $job->id])->row()->texto === $savedMessage, 'Updating model defaults never rewrites captured message history');
$db->where('id', $original->id)->update('com_modelos', ['texto' => $updated->texto, 'ativo' => 1]);
$pickupModel = $db->get_where('com_modelos', ['evento' => 'pronto', 'canal' => 'whatsapp'])->row();
$assert(str_contains($pickupModel->texto, 'combinar a retirada') && ! str_contains($pickupModel->texto, '{link}'), 'Pickup copy has one action instead of competing contact and portal instructions');
$budgetModel = $db->get_where('com_modelos', ['evento' => 'orcamento', 'canal' => 'whatsapp'])->row();
$assert(str_contains($budgetModel->texto, 'aprova ou recusa') && ! str_contains($budgetModel->texto, 'versão atual'), 'Budget copy presents both decisions without internal version terminology');
$db->where('idOs', $id)->update('os', ['status' => 'Faturado']);
$communication->capture($id, 'status', 'customer-status-wording');
$statusMessage = $db->order_by('id', 'desc')->get_where('com_fila', ['os_id' => $id, 'evento' => 'status'])->row();
$assert(str_contains($statusMessage->texto, 'Informações do atendimento atualizadas') && ! str_contains($statusMessage->texto, 'Faturado') && ! str_contains($statusMessage->texto, 'Valores do atendimento registrados'), 'A status label alone does not claim a financial entry exists');
$assert($communication->customerStatus('Faturado', true) === 'Valores do atendimento registrados', 'An actual invoice is described without asserting payment or equipment delivery');
$db->where('idOs', $id)->update('os', ['status' => 'Aberto']);
$assert($communication->customerStatus('INTERNAL_CUSTOM_STAGE') === 'Atendimento atualizado', 'Unknown status codes are not exposed to customers');
