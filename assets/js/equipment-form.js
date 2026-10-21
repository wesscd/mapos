/* Search customers without discarding equipment fields already entered. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    var form = document.getElementById('equipment-form');
    if (!form) return;
    var search = document.getElementById('customer-search');
    var button = document.getElementById('customer-search-button');
    var select = document.getElementById('clientes_id');
    var status = document.getElementById('customer-search-status');
    async function locate() {
        var query = search.value.trim();
        if (!query) { status.textContent = 'Digite o nome do cliente para localizar.'; search.focus(); return; }
        button.disabled = true;
        status.textContent = 'Localizando clientes…';
        try {
            var response = await fetch(form.dataset.customersUrl + '?q=' + encodeURIComponent(query), {credentials: 'same-origin', headers: {Accept: 'application/json'}});
            if (!response.ok) throw new Error('search');
            var data = await response.json();
            if (!Array.isArray(data.clientes)) throw new Error('search');
            var selected = select.selectedOptions[0];
            var previous = selected && selected.value ? selected.cloneNode(true) : null;
            select.replaceChildren(new Option('Selecione o cliente', ''));
            data.clientes.forEach(function (customer) {
                select.add(new Option('#' + customer.idClientes + ' · ' + customer.nomeCliente, String(customer.idClientes)));
            });
            if (previous) {
                if (!Array.from(select.options).some(function (o) { return o.value === previous.value; })) select.add(previous);
                select.value = previous.value;
            }
            status.textContent = data.clientes.length ? 'Selecione o cliente na lista. ' + (data.limitado ? 'Mostrando 50 resultados; refine a busca.' : data.clientes.length + ' resultado(s).') : 'Nenhum cliente encontrado. Tente outro nome. A seleção anterior foi mantida.';
        } catch (error) { status.textContent = 'Não foi possível localizar. Confira sua conexão e tente novamente.'; }
        finally { button.disabled = false; }
    }
    button.addEventListener('click', locate);
    search.addEventListener('keydown', function (event) { if (event.key === 'Enter') { event.preventDefault(); locate(); } });
});
