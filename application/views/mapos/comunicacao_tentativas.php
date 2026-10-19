<section class="mapos-card"><h1>Tentativas de comunicação</h1>
    <p><a href="<?= site_url('comunicacao') ?>">Voltar à comunicação</a></p>
    <ol><?php foreach ($attempts as $attempt) { ?><li><?= html_escape($attempt->created_at . ' · ' . $attempt->status . ' · ' . $attempt->descricao) ?></li><?php } ?></ol>
    <?php if (! $attempts) { ?><p>A mensagem ainda não foi processada.</p><?php } ?>
</section>
