<section class="mapos-workflow"><h1><?= $equipment && isset($equipment->idEquipamentos) ? 'Editar equipamento' : 'Cadastrar equipamento' ?></h1>
    <?php if ($error) { ?><p role="alert" class="alert alert-error"><?= html_escape($error) ?></p><?php } ?>
    <form method="get"><label>Localizar cliente por nome <input name="cliente_busca" maxlength="100"></label><button class="btn">Localizar</button></form>
    <?= form_open(current_url()) ?>
        <label>Cliente (obrigatório)<select name="clientes_id" required><option value="">Selecione</option><?php foreach ($customers as $customer) { ?><option value="<?= (int) $customer->idClientes ?>" <?= $selectedCustomer === (int) $customer->idClientes ? 'selected' : '' ?>>#<?= (int) $customer->idClientes ?> · <?= html_escape($customer->nomeCliente) ?></option><?php } ?></select></label>
        <?php foreach (['equipamento' => ['Equipamento / identificação', 150], 'marca' => ['Marca', 100], 'modelo' => ['Modelo', 80], 'num_serie' => ['Número de série', 80], 'cor' => ['Cor', 45], 'descricao' => ['Observações do cadastro', 150]] as $field => [$label, $limit]) { ?>
            <label><?= html_escape($label) ?><input name="<?= html_escape($field) ?>" maxlength="<?= (int) $limit ?>" value="<?= html_escape($equipment->$field ?? '') ?>" <?= $field === 'equipamento' ? 'required' : '' ?>></label>
        <?php } ?>
        <p>Não registre senhas de acesso ao equipamento nestes campos. O cadastro não confirma seu recebimento físico.</p>
        <button class="btn btn-primary">Salvar equipamento</button> <a class="btn" href="<?= site_url('equipamentos') ?>">Voltar</a>
    </form>
</section>
