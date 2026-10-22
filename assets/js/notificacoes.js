(function () {
    'use strict';
    const examples = {
        '{cliente}': 'Maria', '{os}': '123', '{equipamento}': 'Notebook',
        '{valor}': 'R$ 250,00', '{status}': 'Serviço em execução',
        '{prazo}': '30/10/2026', '{link}': window.location.origin + '/index.php/mine/visualizarOs/123'
    };
    document.querySelectorAll('.notification-model').forEach(function (card) {
        const field = card.querySelector('.notification-text');
        const preview = card.querySelector('.notification-preview');
        function update() {
            let text = field.value;
            // Match the budget total fallback used by the delivery service.
            if (card.dataset.event === 'orcamento' && !text.includes('{valor}')) {
                text = text.includes('{link}')
                    ? text.replaceAll('{link}', '\n\nTotal do orçamento: {valor}.\n\n{link}')
                    : text + '\n\nTotal do orçamento: {valor}.';
            }
            preview.textContent = text.replace(/\{[^{}]+\}/g, function (variable) { return examples[variable] || variable; });
        }
        field.addEventListener('input', update);
        card.querySelectorAll('[data-variable]').forEach(function (button) {
            button.addEventListener('click', function () {
                const start = field.selectionStart;
                const end = field.selectionEnd;
                field.setRangeText(button.dataset.variable, start, end, 'end');
                field.focus();
                update();
            });
        });
        update();
    });
    function openNotifications() {
        if (window.location.hash === '#menu3' && window.jQuery) {
            window.jQuery('a[data-toggle="tab"][href="#menu3"]').tab('show');
        }
    }
    openNotifications();
    window.addEventListener('hashchange', openNotifications);
}());
