<details class="os-actions-menu"><summary>Impressão, navegação e comunicação</summary><div class="os-action-links">
<a class="btn" href="<?= site_url('os') ?>">Lista de OS</a>
<?php if ($osEditing || ($editavel && $osPermission)) { ?><a class="btn" href="<?= site_url('os/' . ($osEditing ? 'visualizar/' : 'editar/') . (int) $result->idOs) ?>"><?= $osEditing ? 'Consultar OS' : 'Editar OS' ?></a><?php } ?>
<a class="btn" href="<?= site_url('os/imprimir/' . (int) $result->idOs) ?>" target="_blank" rel="noopener">Imprimir A4</a>
<a class="btn" href="<?= site_url('os/imprimirTermica/' . (int) $result->idOs) ?>" target="_blank" rel="noopener">Imprimir cupom 80 mm</a>
<?php if ($result->garantias_id) { ?><a class="btn" href="<?= site_url('garantias/imprimirGarantiaOs/' . (int) $result->idOs) ?>" target="_blank" rel="noopener">Imprimir termo de garantia</a><?php } ?>
<?php if ($osPermission) {
    $phone = preg_replace('/[^0-9]/', '', (string) $result->celular_cliente);
    $amount = $result->tipo_desconto ? $result->valor_desconto : $totalProdutos + $totalServico;
    $replacements = [$result->nomeCliente, $result->idOs, $result->status, 'R$ ' . number_format($amount, 2, ',', '.'), strip_tags($result->descricaoProduto), $emitente ? $emitente->nome : '', $emitente ? $emitente->telefone : '', strip_tags($result->observacoes), strip_tags($result->defeito), strip_tags($result->laudoTecnico), date('d/m/Y', strtotime($result->dataFinal)), date('d/m/Y', strtotime($result->dataInicial)), $result->garantia . ' dias'];
    $message = $this->os_model->criarTextoWhats($texto_de_notificacao, $replacements);
    if ($phone) { ?><a class="btn" href="<?= html_escape('https://wa.me/send?phone=55' . $phone . '&text=' . $message) ?>" target="_blank" rel="noopener">Abrir mensagem no WhatsApp</a><?php } ?>
<a class="btn" href="<?= site_url('os/enviar_email/' . (int) $result->idOs) ?>" data-os-confirm-link="Enviar esta OS por e-mail agora?">Enviar OS por e-mail</a>
<?php } ?></div></details>
