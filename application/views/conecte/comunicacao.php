<section class="mapos-card"><h1>Preferências de comunicação</h1>
    <p>Escolha como deseja receber avisos sobre seus atendimentos. Você pode suspender os avisos a qualquer momento. Utilizamos o celular e o e-mail do seu cadastro.</p>
    <?= form_open('mine/comunicacao') ?>
        <label>Receber atualizações por<select name="canal"><?php foreach (['nenhum' => 'Não receber avisos', 'whatsapp' => 'WhatsApp', 'email' => 'E-mail', 'ambos' => 'WhatsApp e e-mail'] as $value => $label) { ?><option value="<?= $value ?>" <?= ($preference->canal ?? 'nenhum') === $value ? 'selected' : '' ?>><?= $label ?></option><?php } ?></select></label>
        <button class="btn btn-primary">Salvar preferência</button>
    </form>
</section>
