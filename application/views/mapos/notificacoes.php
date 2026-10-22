<div class="mapos-workflow notification-settings">
    <section class="mapos-card">
        <h2>Notificações ao cliente</h2>
        <p>Edite aqui os modelos e as regras de envio. A autorização de cada cliente continua sendo necessária. Salvar estas configurações não envia mensagens.</p>
        <a class="btn" href="<?= html_escape(site_url('comunicacao')) ?>">Conectar canais, testar e consultar histórico</a>
    </section>
    <section class="mapos-card"><h3>Automações e horários</h3>
        <label><input form="notification-config" type="checkbox" name="whatsapp" value="1" <?= $notificationSettings['whatsapp'] ? 'checked' : '' ?>> Habilitar avisos automáticos pelo WhatsApp</label>
        <label><input form="notification-config" type="checkbox" name="email" value="1" <?= $notificationSettings['email'] ? 'checked' : '' ?>> Habilitar avisos automáticos por e-mail</label>
        <p>O envio manual de orçamento pelo WhatsApp também funciona com as automações desligadas, respeitando a autorização do cliente e o modelo ativo.</p>
        <p>Horário de Brasília. Fora desse período, as mensagens aguardam na fila.</p>
        <div class="mapos-grid">
            <label>Início (hora)<input form="notification-config" type="number" name="inicio" min="0" max="23" required value="<?= (int) $notificationSettings['inicio'] ?>"></label>
            <label>Fim (hora, exclusiva)<input form="notification-config" type="number" name="fim" min="1" max="24" required value="<?= (int) $notificationSettings['fim'] ?>"></label>
            <label>Intervalo dos lembretes (dias)<input form="notification-config" type="number" name="intervalo_dias" min="1" max="30" required value="<?= (int) $notificationSettings['intervalo_dias'] ?>"></label>
            <label>Limite de lembretes por orçamento ou conclusão<input form="notification-config" type="number" name="limite_lembretes" min="0" max="5" required value="<?= (int) $notificationSettings['limite_lembretes'] ?>"></label>
        </div>
        <button form="notification-config" type="submit" class="btn btn-primary">Salvar automações</button>
    </section>
    <section class="mapos-card"><h3>Modelos das mensagens</h3>
        <p>Os envios manuais e automáticos pelo serviço de comunicação usam estes modelos. Alterações valem para novos envios; mensagens já na fila e no histórico preservam o texto original.</p>
        <p>Use uma ação principal por mensagem. Registrar a OS não confirma o recebimento físico do equipamento. O link abre a área do cliente e exige acesso.</p>
        <p>As prévias abaixo usam dados fictícios e não enviam mensagens.</p>
        <div class="mapos-grid">
        <?php foreach ($notificationModels as $model) {
            $modelId = (int) $model->id;
            $formId = 'notification-model-' . $modelId;
            $title = ($notificationEvents[$model->evento] ?? $model->evento) . ' · ' . ($model->canal === 'whatsapp' ? 'WhatsApp' : 'E-mail'); ?>
            <details class="mapos-card notification-model" data-event="<?= html_escape($model->evento) ?>">
                <summary><?= html_escape($title) ?></summary>
                <label><input form="<?= $formId ?>" type="checkbox" name="ativo" value="1" <?= $model->ativo ? 'checked' : '' ?>> Modelo ativo</label>
                <label for="notification-text-<?= $modelId ?>">Texto</label>
                <textarea form="<?= $formId ?>" id="notification-text-<?= $modelId ?>" name="texto" required maxlength="3000" rows="6" class="notification-text" aria-label="<?= html_escape('Texto de ' . $title) ?>"><?= html_escape($model->texto) ?></textarea>
                <div class="notification-variables" aria-label="Inserir variável">
                    <?php foreach (['cliente' => 'Cliente', 'os' => 'Número da OS', 'equipamento' => 'Equipamento', 'valor' => 'Valor', 'status' => 'Etapa', 'link' => 'Link do portal', 'prazo' => 'Data combinada'] as $variable => $label) { ?>
                        <button type="button" class="btn btn-small" data-variable="<?= html_escape('{' . $variable . '}') ?>" title="<?= html_escape('Inserir {' . $variable . '}') ?>"><?= html_escape($label) ?></button>
                    <?php } ?>
                </div>
                <h4>Prévia ilustrativa</h4><p class="notification-preview" role="status"></p>
                <button form="<?= $formId ?>" type="submit" class="btn btn-primary">Salvar este modelo</button>
            </details>
        <?php } ?>
        </div>
    </section>
    <section class="mapos-card"><h3>Envio manual de OS por e-mail</h3>
        <p>Define quem recebe a cópia completa da OS ao clicar em “Enviar OS por e-mail”. Esse documento usa o layout da OS e não é um aviso automático dos modelos acima.</p>
        <label for="manual-email-recipients">Destinatários</label>
        <select form="notification-recipients" name="os_notification" id="manual-email-recipients">
            <?php foreach (['cliente' => 'Somente o cliente', 'tecnico' => 'Somente o técnico', 'emitente' => 'Somente a empresa', 'todos' => 'Cliente, técnico e empresa', 'nenhum' => 'Desabilitar este envio manual'] as $value => $label) { ?>
                <option value="<?= $value ?>" <?= $configuration['os_notification'] === $value ? 'selected' : '' ?>><?= html_escape($label) ?></option>
            <?php } ?>
        </select>
        <button form="notification-recipients" type="submit" class="btn btn-primary">Salvar destinatários</button>
    </section>
</div>
