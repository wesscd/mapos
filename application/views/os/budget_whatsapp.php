<?php if ($osPermission) {
    $waBudget = $this->os_workflow->budgetWhatsAppPreview((int) $result->idOs);
    $waRetry = $waBudget['existing'] && in_array($waBudget['existing']['status'], ['falhou', 'cancelado'], true);
    $waCanRequest = ! $waBudget['existing'] || $waRetry;
    $waCanConfigure = $this->permission->checkPermission($this->session->userdata('permissao'), 'cSistema');
    $waNeedsConsent = strpos((string) $waBudget['reason'], 'autorização') !== false;
    $waStates = ['pendente' => 'Na fila', 'enviando' => 'Envio em andamento', 'enviado' => 'Enviado', 'entregue' => 'Entregue', 'lido' => 'Lido', 'incerto' => 'Confirmação pendente', 'falhou' => 'Falha no envio', 'cancelado' => 'Envio cancelado'];
    ?>
<section class="os-budget-section" id="os-budget-whatsapp"><h3>WhatsApp do orçamento</h3>
<p><?= $waCanRequest ? 'Confira a prévia e as condições de envio abaixo. Quando o envio estiver liberado, confirme a conferência e clique em “Enviar orçamento por WhatsApp”.' : 'Acompanhe abaixo o envio deste orçamento pelo WhatsApp.' ?> O envio usa o WhatsApp conectado ao sistema. O cliente consulta e responde ao orçamento na área do cliente.</p>
<?php if ($waBudget['existing']) {
    $waJob = $waBudget['existing']; ?>
<p role="status"><strong><?= html_escape($waStates[$waJob['status']] ?? 'Confira o acompanhamento') ?></strong> · Mensagem #<?= (int) $waJob['id'] ?> · <?= html_escape(date('d/m/Y H:i', strtotime($waJob['created_at']))) ?></p>
<?php if ($waJob['detail']) { ?><p><?= html_escape($waJob['detail']) ?></p><?php } ?>
<p>Já existe uma comunicação para este orçamento e destinatário. Um novo clique não cria outra mensagem. Confirmação de envio não significa aprovação do orçamento.</p>
<?php if (in_array($waJob['status'], ['incerto', 'falhou', 'cancelado'], true)) { ?><p>Confira a tentativa antes de repetir. Em falhas ou cancelamentos, a nova tentativa usa a mesma mensagem, após sua confirmação. Envios sem confirmação exigem conferência na Central e não são repetidos automaticamente.</p><?php } ?>
<a class="btn" href="<?= html_escape(current_url() . '#os-pane-orcamento') ?>">Atualizar acompanhamento</a>
<?php } ?>
<details class="os-inline-action" <?= $waCanRequest ? 'open' : '' ?>><summary><?= $waBudget['existing'] ? 'Conferir mensagem registrada' : 'Prévia da mensagem' ?></summary>
<dl class="os-summary-grid"><div><dt>Cliente</dt><dd><?= html_escape($waBudget['customer']) ?></dd></div><div><dt>Destinatário</dt><dd><?= $waBudget['destination'] ? html_escape('+' . $waBudget['destination']) : 'Telefone não disponível' ?></dd></div><div><dt>Total salvo do orçamento</dt><dd>R$ <?= html_escape(number_format($waBudget['value'], 2, ',', '.')) ?></dd></div></dl>
<p>Confira os serviços, produtos e o desconto apresentados acima. A prévia usa os dados salvos. Alterar os valores ou o diagnóstico exige outra conferência.</p>
<pre class="os-message-preview"><?= html_escape($waBudget['text'] ?: 'Ative o modelo de orçamento do WhatsApp para preparar a mensagem.') ?></pre>
<p>O link exige acesso do cliente ao portal. O envio respeita o horário configurado: <?= (int) $waBudget['window'][0] ?>h às <?= (int) $waBudget['window'][1] ?>h. Se o WhatsApp estiver desconectado, a fila aguarda uma nova tentativa dentro das regras existentes.</p>
</details>
<?php if ($waBudget['reason']) { ?>
<div class="os-feedback is-error" id="wa-budget-blocked" role="status"><strong>Envio indisponível.</strong> <?= html_escape($waBudget['reason']) ?>
<?php if ($waNeedsConsent) { ?><p><?= $waCanConfigure ? 'Se o cliente já autorizou o contato, registre a autorização no botão abaixo. Depois, volte à OS e atualize esta página para enviar o orçamento.' : 'Solicite a um administrador que registre a autorização informada pelo cliente na Central de Comunicação.' ?></p><?php } ?>
</div>
<?php if ($waCanRequest) { ?><p><button type="button" class="btn btn-success" disabled aria-describedby="wa-budget-blocked"><?= $waRetry ? 'Tentar envio novamente por WhatsApp' : 'Enviar orçamento por WhatsApp' ?></button></p><?php } ?>
<?php } ?>
<?php if (! $waBudget['reason'] && $waCanRequest) { ?>
<?= form_open('acompanhamento/salvar/' . (int) $result->idOs) ?>
<input type="hidden" name="acao" value="orcamento_whatsapp"><input type="hidden" name="retorno" value="<?= $osEditing ? 'editar' : 'visualizar' ?>"><input type="hidden" name="previa" value="<?= html_escape($waBudget['fingerprint']) ?>">
<label for="wa-budget-confirm"><input type="checkbox" id="wa-budget-confirm" name="confirmar" value="1" required> Conferi o destinatário, os valores salvos e a mensagem apresentada.</label>
<button type="submit" class="btn btn-success"><?= $waRetry ? 'Tentar envio novamente por WhatsApp' : 'Enviar orçamento por WhatsApp' ?></button>
</form>
<p>Este envio funciona mesmo com os avisos automáticos desligados. A ação disponibiliza o orçamento para aprovação, se ainda não estiver disponível, e inclui somente o WhatsApp na fila, respeitando o horário configurado.</p>
<?php if (! $waBudget['existing'] && $waBudget['approval']) { ?><a class="btn" target="_blank" rel="noopener" href="<?= html_escape('https://wa.me/' . $waBudget['destination'] . '?text=' . rawurlencode($waBudget['text'])) ?>">Abrir mensagem manualmente no WhatsApp</a><?php } ?>
<?php } ?>
<?php if ($waCanConfigure) { ?><a class="btn" href="<?= site_url('comunicacao?cliente=' . (int) $result->clientes_id) . '#preferencias-cliente' ?>"><?= $waNeedsConsent ? 'Registrar autorização do cliente' : 'Abrir preferências de comunicação deste cliente' ?></a><?php } ?>
</section><?php } ?>
