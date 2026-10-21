$(function () {
    var trigger;
    $('.ui-page [data-toggle="modal"]').on('click', function () {
        trigger = this;
        var modal = $($(this).attr('data-target'));
        modal.find('input[name="id"]').val($(this).attr('data-item-id') || '');
        modal.find('[name="estoqueAtual"]').val($(this).attr('data-stock') || '');
        modal.find('[name="estoque"]').val('');
    });
    $('.ui-catalog-modal').on('shown', function () { $(this).find('[data-dismiss="modal"]').first().focus(); });
    $('.ui-catalog-modal').on('hidden', function () {
        $(this).find('input[name="id"]').val('');
        if (trigger) trigger.focus();
    });
});
