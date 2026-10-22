<details class="os-actions-menu"><summary>Impressão, navegação e comunicação</summary><div class="os-action-links">
<a class="btn" href="<?= site_url('os') ?>">Lista de OS</a>
<?php if ($osEditing || ($editavel && $osPermission)) { ?><a class="btn" href="<?= site_url('os/' . ($osEditing ? 'visualizar/' : 'editar/') . (int) $result->idOs) ?>"><?= $osEditing ? 'Consultar OS' : 'Editar OS' ?></a><?php } ?>
<a class="btn" href="<?= site_url('os/imprimir/' . (int) $result->idOs) ?>" target="_blank" rel="noopener">Imprimir A4</a>
<a class="btn" href="<?= site_url('os/imprimirTermica/' . (int) $result->idOs) ?>" target="_blank" rel="noopener">Imprimir cupom 80 mm</a>
<?php if ($result->garantias_id) { ?><a class="btn" href="<?= site_url('garantias/imprimirGarantiaOs/' . (int) $result->idOs) ?>" target="_blank" rel="noopener">Imprimir termo de garantia</a><?php } ?>
<?php if ($osPermission) {
    $this->load->library('comunicacao_service');
    $customer = (object) ['celular' => $result->celular_cliente, 'telefone' => $result->telefone_cliente ?? ''];
    $phone = $this->comunicacao_service->destination('whatsapp', $customer);
    $message = $this->comunicacao_service->orderMessage($result->idOs);
    if ($phone && $message !== '') { ?><a class="btn" href="<?= html_escape('https://wa.me/' . $phone . '?text=' . rawurlencode($message)) ?>" target="_blank" rel="noopener">Abrir mensagem no WhatsApp</a><?php } ?>
<a class="btn" href="<?= site_url('os/enviar_email/' . (int) $result->idOs) ?>" data-os-confirm-link="Enviar esta OS por e-mail agora?">Enviar OS por e-mail</a>
<?php } ?></div></details>
