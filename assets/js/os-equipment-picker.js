/* Equipment belongs to the customer selected by the existing OS autocomplete. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    const picker = document.getElementById('os-equipment-picker');
    if (!picker) return;
    const customer = document.getElementById('clientes_id');
    const customerName = document.getElementById('cliente');
    const select = document.getElementById('equipamento_id');
    const help = document.getElementById('os-equipment-help');
    const refresh = document.getElementById('os-equipment-refresh');
    const newLink = document.getElementById('os-equipment-new');
    let request = null;
    let version = 0;
    let initialSelection = picker.dataset.selected || '';

    async function load(keepSelection) {
        const currentVersion = ++version;
        if (request) request.abort();
        const id = customer.value;
        const selected = keepSelection ? select.value : initialSelection;
        initialSelection = '';
        select.replaceChildren(new Option(id ? 'Carregando equipamentos...' : 'Selecione o cliente primeiro', ''));
        select.disabled = true;
        refresh.hidden = !id;
        refresh.style.display = id ? 'inline-block' : 'none';
        if (newLink) {
            newLink.hidden = !id;
            newLink.style.display = id ? 'inline-block' : 'none';
            newLink.href = picker.dataset.newUrl + '?cliente=' + encodeURIComponent(id);
        }
        if (!id) {
            help.textContent = 'Selecione o cliente na lista de sugestões para consultar seus equipamentos.';
            return;
        }
        help.textContent = 'Consultando equipamentos do cliente...';
        const controller = new AbortController();
        request = controller;
        const timeout = setTimeout(() => controller.abort(), 10000);
        try {
            const response = await fetch(picker.dataset.endpoint + '/' + encodeURIComponent(id), {signal: controller.signal, headers: {Accept: 'application/json'}});
            if (!response.ok) throw new Error('Failed to load equipment');
            const data = await response.json();
            if (currentVersion !== version || customer.value !== id) return;
            select.replaceChildren(new Option('Sem vínculo de equipamento neste momento', ''));
            for (const equipment of data.equipamentos) {
                const label = '#' + equipment.idEquipamentos + ' · ' + equipment.equipamento
                    + (equipment.modelo ? ' · ' + equipment.modelo : '')
                    + (equipment.num_serie ? ' · Série: ' + equipment.num_serie : '');
                select.add(new Option(label, equipment.idEquipamentos));
            }
            if (selected && Array.from(select.options).some(option => option.value === selected)) select.value = selected;
            select.disabled = false;
            help.textContent = data.equipamentos.length ? (data.limitado ? 'Exibindo os 200 cadastros mais recentes deste cliente.' : 'Selecione o equipamento que será atendido.')
                : 'Este cliente ainda não possui equipamentos cadastrados. Você pode cadastrar em outra aba e atualizar a lista, ou continuar sem vínculo.';
        } catch (error) {
            if (currentVersion !== version) return;
            select.replaceChildren(new Option('Lista indisponível — tente atualizar', ''));
            help.textContent = 'Não foi possível carregar os equipamentos. Clique em Atualizar equipamentos para tentar novamente.';
        } finally {
            clearTimeout(timeout);
        }
    }
    window.jQuery(customer).on('change', function () { load(false); });
    customerName.addEventListener('input', function () {
        customer.value = '';
        load(false);
    });
    refresh.addEventListener('click', function () { load(true); });
    load(false);
});
