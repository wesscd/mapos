<section class="os-budget-section"><h3>Financeiro</h3>
<?php if (! (int) $result->faturado && $osPermission) { ?>
<details class="os-inline-action" id="os-invoice"><summary>Faturar ordem de serviço</summary>
<p>Será registrado um lançamento de <strong>R$ <?= html_escape(number_format($osNet, 2, ',', '.')) ?></strong>, conforme os itens e o desconto salvos.</p>
<form id="formFaturar" method="post" action="<?= site_url('os/faturar') ?>" data-os-ajax data-os-message="OS faturada com sucesso.">
<input type="hidden" name="os_id" value="<?= (int) $result->idOs ?>"><input type="hidden" name="tipo" value="receita"><input type="hidden" name="valor" value="<?= html_escape((string) $osGross) ?>"><input type="hidden" name="cliente" value="<?= html_escape($result->nomeCliente) ?>"><input type="hidden" name="clientes_id" value="<?= (int) $result->clientes_id ?>">
<div class="os-field-grid"><label for="invoice-description">Descrição<input id="invoice-description" name="descricao" required value="Fatura da OS #<?= (int) $result->idOs ?>"></label><label for="invoice-due">Data de vencimento *<input id="invoice-due" class="datepicker" name="vencimento" required placeholder="dd/mm/aaaa" autocomplete="off"></label></div>
<label for="invoice-paid"><input id="invoice-paid" name="recebido" type="checkbox" value="1"> Pagamento já recebido</label>
<div class="os-field-grid" data-os-paid-fields hidden><label for="invoice-paid-date">Data do recebimento<input id="invoice-paid-date" class="datepicker" name="recebimento" placeholder="dd/mm/aaaa" autocomplete="off"></label><label for="invoice-method">Forma de pagamento<select id="invoice-method" name="formaPgto"><?php foreach (['Dinheiro', 'Pix', 'Cartão de Crédito', 'Cartão de Débito', 'Boleto', 'Depósito', 'Cheque'] as $method) { ?><option><?= html_escape($method) ?></option><?php } ?></select></label></div>
<button type="submit" class="btn btn-primary">Confirmar faturamento</button></form></details>
<?php } elseif ($result->faturado) { ?><p>OS faturada. Total: R$ <?= html_escape(number_format($osNet, 2, ',', '.')) ?>.</p><?php } ?>
<?php if (! $osEditing) { ?>
<details class="os-inline-action"><summary>Gerar cobrança por gateway</summary><p>A criação da cobrança depende de um provedor configurado.</p><?php $this->load->config('payment_gateways');
    $gateways = $this->config->item('payment_gateways'); ?>
<form method="post" action="<?= site_url('cobrancas/adicionar') ?>" data-os-ajax data-os-payment>
<input type="hidden" name="id" value="<?= (int) $result->idOs ?>"><input type="hidden" name="tipo" value="os">
<label for="payment-gateway">Gateway de pagamento<select id="payment-gateway" name="gateway_de_pagamento" required><option value="">Escolha o provedor</option><?php foreach ($gateways as $gateway) { ?><option value="<?= html_escape($gateway['library_name']) ?>" data-methods="<?= html_escape(json_encode($gateway['payment_methods'])) ?>"><?= html_escape($gateway['name']) ?></option><?php } ?></select></label>
<label for="payment-method">Forma de pagamento<select id="payment-method" name="forma_pagamento" required disabled><option value="">Selecione o provedor primeiro</option></select></label><button type="submit" class="btn btn-primary">Gerar cobrança</button></form></details>
<?php if (! empty($configuration['pix_key']) && ! empty($qrCode)) { ?><details class="os-inline-action"><summary>Pagamento via Pix</summary><p>Total: R$ <?= html_escape(number_format($osNet, 2, ',', '.')) ?></p><img class="os-pix-image" src="<?= html_escape($qrCode) ?>" alt="QR Code para pagamento via Pix"><p>Chave Pix: <strong><?= html_escape($chaveFormatada) ?></strong></p><button type="button" class="btn" data-os-copy="<?= html_escape($pixCode) ?>">Copiar Pix Copia e Cola</button></details><?php } ?>
<?php } ?>
</section>
