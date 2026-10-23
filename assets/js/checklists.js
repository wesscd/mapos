(function () {
    'use strict';
    const equipment = document.getElementById('checklist-equipment');
    const type = document.getElementById('checklist-type');
    if (!equipment || !type) return;
    equipment.addEventListener('change', function () {
        const selected = equipment.options[equipment.selectedIndex];
        type.value = selected.dataset.type || '';
    });
}());
