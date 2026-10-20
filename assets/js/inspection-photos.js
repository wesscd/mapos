document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-vistoria-camera]');
    if (!button) return;
    const input = button.closest('fieldset').querySelector('[data-vistoria-upload]');
    const camera = document.createElement('input');
    camera.type = 'file';
    camera.accept = 'image/jpeg,image/png';
    camera.capture = 'environment';
    camera.style.display = 'none';
    document.body.appendChild(camera);
    camera.addEventListener('change', function () {
        if (camera.files.length) {
            input.files = camera.files;
            input.dispatchEvent(new Event('change', {bubbles: true}));
        }
        camera.remove();
    }, {once: true});
    camera.addEventListener('cancel', function () { camera.remove(); }, {once: true});
    camera.click();
});
document.addEventListener('change', function (event) {
    const input = event.target;
    if (!input.matches('[data-vistoria-upload]')) return;
    const preview = input.closest('fieldset').querySelector('[data-vistoria-preview]');
    if (preview.dataset.url) URL.revokeObjectURL(preview.dataset.url);
    preview.hidden = true;
    preview.removeAttribute('src');
    input.setCustomValidity('');
    const file = input.files[0];
    if (!file) return;
    if (file.size > 8388608 || !['image/jpeg', 'image/png'].includes(file.type)) {
        input.setCustomValidity('Selecione uma foto JPEG ou PNG de até 8 MB.');
        input.reportValidity();
        return;
    }
    preview.dataset.url = URL.createObjectURL(file);
    preview.src = preview.dataset.url;
    preview.hidden = false;
});
