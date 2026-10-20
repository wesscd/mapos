    <details class="mapos-card mapos-history" id="os-timeline"><summary>Histórico do atendimento <span class="mapos-muted">— últimos 100 eventos</span></summary>
        <?php if (! $workflow['eventos']) { ?><p>O histórico começa a registrar eventos a partir desta atualização.</p><?php } ?>
        <ol><?php foreach ($workflow['eventos'] as $event) { ?>
            <li><time><?= html_escape(date('d/m/Y H:i', strtotime($event->created_at))) ?></time> <?= html_escape($event->descricao) ?></li>
        <?php } ?></ol>
    </details>