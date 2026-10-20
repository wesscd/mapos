document.addEventListener('DOMContentLoaded', function () {
    const board = document.querySelector('.mapos-kanban-board');
    if (!board) return;
    let dragged = null;
    board.addEventListener('dragstart', function (event) {
        const card = event.target.closest('[data-kanban-card][draggable="true"]');
        if (!card || event.target.closest('input,select,button,a')) return;
        dragged = card;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', card.dataset.kanbanCard);
    });
    board.addEventListener('dragover', function (event) {
        const column = event.target.closest('[data-kanban-target]');
        if (dragged && column && column.dataset.kanbanTarget) event.preventDefault();
    });
    board.addEventListener('drop', function (event) {
        const column = event.target.closest('[data-kanban-target]');
        if (!dragged || !column || !column.dataset.kanbanTarget) return;
        event.preventDefault();
        const form = dragged.querySelector('[data-kanban-form]');
        const select = form.querySelector('select[name="status"]');
        if (select.value === column.dataset.kanbanTarget) return;
        select.value = column.dataset.kanbanTarget;
        form.requestSubmit();
    });
    board.addEventListener('dragend', function () { dragged = null; });
    board.addEventListener('submit', function (event) {
        const form = event.target;
        if (!form.matches('[data-kanban-form]')) return;
        if (form.querySelector('select[name="status"]').value === 'Cancelado' && !window.confirm('Cancelar esta OS? O estoque e os avisos serão atualizados conforme as regras do atendimento.')) {
            event.preventDefault();
            return;
        }
        form.querySelector('button').disabled = true;
    });
});
