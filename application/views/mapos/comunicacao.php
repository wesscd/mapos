<div class="mapos-workflow" id="communication" data-status-url="<?= html_escape(site_url('comunicacao/status')) ?>">
    <div class="mapos-section-heading"><div><span class="mapos-eyebrow">RELACIONAMENTO</span><h1>Comunicação com o cliente</h1></div></div>
    <p>Configure os canais, envie um teste e habilite as automações. O cliente precisa autorizar o canal para receber avisos.</p>
    <div class="mapos-grid">
        <section class="mapos-card"><h2>WhatsApp</h2>
            <p role="status" id="wa-state">Consultando conexão…</p>
            <p id="wa-account"></p><img id="wa-qr" hidden width="320" height="320" alt="QR Code para conectar o WhatsApp" style="max-width:100%;height:auto">
            <p id="wa-diagnostic" class="mapos-muted"></p>
            <p class="mapos-muted">No celular, abra WhatsApp → Aparelhos conectados → Conectar aparelho. O QR Code é renovado automaticamente.</p>
            <?= form_open('comunicacao/salvar', ['class' => 'mapos-inline-form']) ?>
                <button class="btn btn-primary" name="acao" value="conectar">Conectar / gerar QR Code</button>
                <button class="btn" name="acao" value="desconectar">Desconectar e apagar sessão</button>
            </form>
        </section>
        <section class="mapos-card"><h2>E-mail — Gmail</h2>
            <p><?= $smtpReady ? 'SMTP configurado. Envie um teste para confirmar o acesso.' : 'SMTP ainda não configurado.' ?></p>
            <?= form_open('comunicacao/salvar') ?><input type="hidden" name="acao" value="smtp">
                <label for="smtp-email">Conta de e-mail</label><input id="smtp-email" type="email" name="email" required value="<?= html_escape($smtpEmail) ?>" autocomplete="off">
                <label for="smtp-password">Senha de app do Google</label><input id="smtp-password" type="password" name="senha" required autocomplete="new-password">
                <p class="mapos-muted">Use uma senha de app gerada na conta Google com verificação em duas etapas. A senha fica em armazenamento privado e não é exibida novamente.</p>
                <button class="btn btn-primary">Salvar Gmail</button>
            </form>
        </section>
        <section class="mapos-card"><h2>Automações e horários</h2>
            <?= form_open('comunicacao/salvar') ?><input type="hidden" name="acao" value="config">
                <label><input type="checkbox" name="whatsapp" value="1" <?= $settings['whatsapp'] ? 'checked' : '' ?>> Habilitar avisos automáticos pelo WhatsApp</label>
                <label><input type="checkbox" name="email" value="1" <?= $settings['email'] ? 'checked' : '' ?>> Habilitar avisos automáticos por e-mail</label>
                <p>Horário de Brasília. Fora do horário, os avisos aguardam na fila.</p>
                <label>Início (hora)<input type="number" name="inicio" min="0" max="23" required value="<?= (int) $settings['inicio'] ?>"></label>
                <label>Fim (hora, exclusiva)<input type="number" name="fim" min="1" max="24" required value="<?= (int) $settings['fim'] ?>"></label>
                <label>Intervalo dos lembretes (dias)<input type="number" name="intervalo_dias" min="1" max="30" required value="<?= (int) $settings['intervalo_dias'] ?>"></label>
                <label>Limite de lembretes por orçamento ou conclusão<input type="number" name="limite_lembretes" min="0" max="5" required value="<?= (int) $settings['limite_lembretes'] ?>"></label>
                <button class="btn btn-primary">Salvar automações</button>
            </form>
        </section>
        <section class="mapos-card"><h2>Enviar teste</h2>
            <p>O teste é enviado ao destinatário informado, mesmo com as automações desativadas ou fora do horário. Use um contato sob seu controle.</p>
            <?= form_open('comunicacao/salvar') ?><input type="hidden" name="acao" value="teste">
                <label>Canal<select name="canal"><option value="whatsapp">WhatsApp</option><option value="email">E-mail</option></select></label>
                <label>Telefone com DDD (outros países: +DDI), ou e-mail<input name="destino" required maxlength="254"></label>
                <button class="btn btn-primary">Enviar mensagem de teste</button>
            </form>
        </section>
    </div>
    <section class="mapos-card"><h2>Preferências por cliente</h2>
        <form method="get" action="<?= site_url('comunicacao') ?>" class="mapos-inline-form"><label for="pref-client">Nome ou código do cliente</label><input id="pref-client" name="busca" maxlength="100" required value="<?= html_escape($search) ?>"><button class="btn">Consultar</button></form>
        <?php if ($search !== '') { ?><ul><?php foreach ($customers as $found) { ?><li><a href="<?= site_url('comunicacao?cliente=' . (int) $found->idClientes) ?>">#<?= (int) $found->idClientes ?> — <?= html_escape($found->nomeCliente) ?></a></li><?php } ?></ul><?php if (! $customers) { ?><p>Nenhum cliente encontrado.</p><?php } ?><?php } ?>
        <?php if ($customer) { ?>
            <p><strong><?= html_escape($customer->nomeCliente) ?></strong> · Celular: <?= html_escape($customer->celular ?: $customer->telefone) ?> · E-mail: <?= html_escape($customer->email) ?></p>
            <?= form_open('comunicacao/salvar') ?><input type="hidden" name="acao" value="preferencia"><input type="hidden" name="cliente_id" value="<?= (int) $customer->idClientes ?>">
                <label>Canal autorizado<select name="canal"><?php foreach (['nenhum' => 'Não receber avisos', 'whatsapp' => 'WhatsApp', 'email' => 'E-mail', 'ambos' => 'WhatsApp e e-mail'] as $key => $label) { ?><option value="<?= $key ?>" <?= ($preference->canal ?? 'nenhum') === $key ? 'selected' : '' ?>><?= $label ?></option><?php } ?></select></label>
                <label>Registro da autorização do cliente<input name="evidencia" maxlength="250" value="<?= html_escape($preference->evidencia ?? '') ?>" placeholder="Ex.: autorizado na recepção em 19/10/2026"></label>
                <button class="btn btn-primary">Salvar preferência</button>
            </form>
        <?php } ?>
    </section>
    <details class="mapos-card"><summary>Modelos das mensagens</summary>
        <p>Campos disponíveis: {cliente}, {os}, {equipamento}, {prazo}, {status}, {link}. O link exige acesso à área do cliente.</p>
        <p>Use uma ação principal por mensagem: consultar o atendimento, responder ao orçamento ou combinar a retirada. O campo {status} usa descrições voltadas ao cliente. Registrar uma solicitação não confirma o recebimento físico do equipamento.</p>
        <div class="mapos-grid"><?php foreach ($models as $model) { ?>
            <section><h3><?= html_escape($events[$model->evento]) ?> · <?= html_escape($model->canal) ?></h3>
                <?= form_open('comunicacao/salvar') ?><input type="hidden" name="acao" value="modelo"><input type="hidden" name="id" value="<?= (int) $model->id ?>">
                    <label><input type="checkbox" name="ativo" value="1" <?= $model->ativo ? 'checked' : '' ?>> Modelo ativo</label>
                    <label>Texto<textarea name="texto" required maxlength="3000" rows="4" style="width:95%" aria-label="Texto de <?= html_escape($events[$model->evento] . ' ' . $model->canal) ?>"><?= html_escape($model->texto) ?></textarea></label>
                    <button class="btn">Salvar modelo</button>
                </form>
            </section>
        <?php } ?></div>
    </details>
    <section class="mapos-card"><h2>Histórico de comunicação</h2>
        <p>Últimas 100 mensagens. “Enviado” indica aceitação pelo serviço; “entregue” e “lido” dependem de confirmação do WhatsApp. “Incerto” exige conferência e não gera reenvio automático.</p>
        <div class="mapos-table-wrap"><table class="table table-bordered"><thead><tr><th>Data / OS</th><th>Canal / destino</th><th>Mensagem</th><th>Situação</th><th>Ação</th></tr></thead><tbody>
        <?php foreach ($messages as $message) { ?>
            <tr><td><?= html_escape($message->created_at) ?><br><?= $message->os_id ? '#' . (int) $message->os_id : 'Teste' ?></td><td><?= html_escape($message->canal) ?><br><?= html_escape($message->destino) ?></td>
                <td><details><summary><?= html_escape($events[$message->evento] ?? 'Teste') ?></summary><p style="white-space:pre-wrap"><?= html_escape($message->texto) ?></p>
                    <p><?= html_escape($message->erro ?? '') ?></p><a href="<?= site_url('comunicacao/tentativas/' . (int) $message->id) ?>">Consultar tentativas</a></details></td>
                <td><?= html_escape($message->status) ?><br><?= (int) $message->tentativas ?> tentativa(s)</td><td>
                <?php if (in_array($message->status, ['pendente', 'falhou'], true)) { ?>
                    <?= form_open('comunicacao/salvar') ?><input type="hidden" name="acao" value="mensagem"><input type="hidden" name="id" value="<?= (int) $message->id ?>">
                        <button class="btn btn-small" name="operacao" value="cancelar">Cancelar</button>
                        <?php if ($message->status === 'falhou') { ?><button class="btn btn-small" name="operacao" value="repetir">Tentar novamente</button><?php } ?>
                    </form>
                <?php } ?></td></tr>
        <?php } ?>
        <?php if (! $messages) { ?><tr><td colspan="5">Nenhuma mensagem registrada. Configure os canais e envie um teste.</td></tr><?php } ?>
        </tbody></table></div>
    </section>
</div>
<script src="<?= base_url('assets/js/comunicacao.js') ?>" defer></script>
