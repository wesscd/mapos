const {test} = require('node:test');
const assert = require('node:assert/strict');
const {readFileSync} = require('node:fs');
const {join} = require('node:path');
const vm = require('node:vm');

function setup() {
    const inputs = Array.from({length: 5}, () => ({files: []}));
    const methods = {};
    const messages = {};
    const form = {};
    const $ = target => target === form ? {find: selector => {
        assert.equal(selector, '[data-vistoria-upload]');
        return {each: callback => inputs.forEach(input => callback.call(input))};
    }} : {rules: (command, rules) => {
        assert.equal(command, 'add');
        target.rules = rules;
    }};
    $.validator = {addMethod: (name, method, message) => { methods[name] = method; messages[name] = message; }};
    $.data = target => target === form ? {} : null;
    const context = {window: {jQuery: $}, document: {addEventListener() {}}};
    vm.runInNewContext(readFileSync(join(__dirname, '../assets/js/inspection-photos.js'), 'utf8'), context);
    context.window.MaposInspectionPhotos.configureValidation(form);
    return {inputs, method: methods.maposInspectionPhoto, message: messages.maposInspectionPhoto};
}

test('all five angles override the incompatible legacy accept rule without removing MIME validation', () => {
    const {inputs} = setup();
    for (const input of inputs) {
        assert.equal(input.rules.accept, false);
        assert.equal(input.rules.maposInspectionPhoto, true);
    }
});
for (const [name, type] of [['foto.JPG', 'image/jpeg'], ['foto.jpeg', 'image/jpeg'], ['foto.png', 'image/png'], ['camera-without-extension', 'image/jpeg']]) {
    test('accepts valid image metadata: ' + name, () => {
        const {method} = setup();
        assert.equal(method(name, {files: [{name, type, size: 1024}]}), true);
    });
}
test('empty optional angles do not block OS creation', () => {
    assert.equal(setup().method('', {files: []}), true);
});
test('unsupported types cannot pass by using a JPEG filename', () => {
    const {method, message} = setup();
    assert.equal(method('foto.jpg', {files: [{type: 'image/gif', size: 1024}]}), false);
    assert.equal(message, 'Selecione uma foto JPEG ou PNG de até 8 MB.');
});
test('the eight megabyte limit is inclusive and enforced by the form validator', () => {
    const {method} = setup();
    assert.equal(method('foto.png', {files: [{type: 'image/png', size: 8388608}]}), true);
    assert.equal(method('foto.png', {files: [{type: 'image/png', size: 8388609}]}), false);
});
