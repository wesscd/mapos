/* Shared customer create/edit interactions. */
$(function () {
    var form = $('#formCliente');
    if (!form.length) return;
    $('#customer-password-toggle').on('click', function () {
        var input = $('#senha');
        var visible = input.attr('type') === 'password';
        input.attr('type', visible ? 'text' : 'password');
        $(this).attr('aria-pressed', visible ? 'true' : 'false').text(visible ? 'Ocultar senha' : 'Mostrar senha');
    });
    $.getJSON(form.attr('data-states-url'), function (data) {
        var state = $('#estado');
        data.estados.forEach(function (entry) { state.append(new Option(entry.nome, entry.sigla)); });
        state.val(state.attr('data-selected') || '');
    });
    form.validate({
        rules: { nomeCliente: { required: true } },
        messages: { nomeCliente: { required: 'Informe o nome ou a razão social.' } },
        errorClass: 'help-inline', errorElement: 'span',
        highlight: function (element) { $(element).closest('.control-group').addClass('error'); },
        unhighlight: function (element) { $(element).closest('.control-group').removeClass('error'); }
    });
});
