/* OS interaction layer: server-rendered panels, explicit forms, no HTML fragment replacement. */
(function () {
    'use strict';
    function init() {
        const root = document.querySelector('[data-os-root]');
        if (!root) return;
        const $ = window.jQuery;
        const header = document.querySelector('[data-os-workspace]');
        const tabs = Array.from(document.querySelectorAll('[data-os-tab]'));
        const panels = Array.from(document.querySelectorAll('[data-os-panel]'));
        const main = document.querySelector('[data-os-main]');
        const feedback = root.querySelector('[data-os-feedback]');
        const draft = root.querySelector('[data-os-draft]');
        const storageKey = header ? 'mapos.os.tab.' + header.dataset.osWorkspace : null;
        let navigating = false;
        let busy = false;
        let mainDirty = false;
        const aliases = {tab1:'resumo',tab2:'orcamento',tab3:'orcamento',tab4:'orcamento',tab5:'historico',tab6:'historico',equipamentos:'equipamento','equipamentos-saida':'entrega',acompanhamento:'resumo'};
        function stored(key) { try { return sessionStorage.getItem(key); } catch (_) { return null; } }
        function store(key, value) { try { sessionStorage.setItem(key, value); } catch (_) { /* Optional navigation persistence. */ } }
        function notify(message, error) {
            feedback.textContent = message;
            feedback.hidden = false;
            feedback.classList.toggle('is-error', !!error);
            feedback.scrollIntoView({block:'nearest'});
        }
        function open(key, focus, hash) {
            if (!tabs.length) return;
            if (!tabs.some(tab => tab.dataset.osTab === key)) key = 'resumo';
            tabs.forEach(tab => {
                const active = tab.dataset.osTab === key;
                tab.setAttribute('aria-selected', String(active));
                tab.tabIndex = active ? 0 : -1;
                if (active && focus) tab.focus();
            });
            panels.forEach(panel => { panel.hidden = panel.dataset.osPanel !== key; });
            if (storageKey) store(storageKey, key);
            if (hash) history.replaceState(null, '', '#os-pane-' + key);
        }
        function fromHash() {
            const hash = location.hash.slice(1);
            open(document.querySelector('[data-os-form-errors]') ? 'resumo' : (hash.startsWith('os-pane-') ? hash.slice(8) : aliases[hash]) || stored(storageKey) || 'resumo', false, false);
        }
        if (tabs.length > 0 && tabs.length === panels.length && tabs.every(tab => panels.some(panel => panel.dataset.osPanel === tab.dataset.osTab))) {
            document.body.classList.add('os-workspace-ready');
            fromHash();
            window.addEventListener('hashchange', fromHash);
            document.addEventListener('click', event => {
                const target = event.target.closest('[data-os-tab], [data-os-open]');
                if (!target) return;
                event.preventDefault();
                open(target.dataset.osTab || target.dataset.osOpen, false, true);
            });
            tabs.forEach((tab, index) => tab.addEventListener('keydown', event => {
                const offsets = {ArrowRight:(index+1)%tabs.length,ArrowLeft:(index+tabs.length-1)%tabs.length,Home:0,End:tabs.length-1};
                if (!(event.key in offsets)) return;
                event.preventDefault();
                open(tabs[offsets[event.key]].dataset.osTab, true, true);
            }));
            if (location.hash) window.addEventListener('load', () => window.scrollTo(0,0), {once:true});
        }
        document.addEventListener('invalid', event => {
            const panel = event.target.closest('[data-os-panel]');
            if (panel) open(panel.dataset.osPanel, false, true);
            let ancestor = event.target.parentElement;
            while (ancestor) { if (ancestor.tagName === 'DETAILS') ancestor.open = true; ancestor = ancestor.parentElement; }
        }, true);
        if ($?.fn.datepicker) $('.os-workspace-v2 .datepicker').datepicker({dateFormat:'dd/mm/yy'});
        if ($?.fn.trumbowyg) $('.os-workspace-v2 .editor').trumbowyg({lang:'pt_br', btns:[['strong','em'],['unorderedList','orderedList'],['removeformat']], autogrow:true});
        function mainState() {
            if (!main) return '';
            return JSON.stringify(Array.from(new FormData(main).entries()).filter(([key]) => key !== document.querySelector('meta[name="csrf-token-name"]')?.content).map(([key,value]) => [key, value instanceof File ? (value.name ? [value.name,value.size,value.lastModified] : null) : value]));
        }
        const baseline = mainState();
        function updateDraft() {
            mainDirty = mainState() !== baseline;
            if (draft) draft.hidden = !mainDirty;
        }
        document.addEventListener('input', updateDraft);
        document.addEventListener('change', updateDraft);
        if ($) $(document).on('tbwchange', '.editor', updateDraft);
        window.addEventListener('beforeunload', event => {
            if (!navigating && mainDirty) { event.preventDefault(); event.returnValue = ''; }
        });
        if (root.dataset.osEditing === '1') root.querySelectorAll('form').forEach(form => {
            if (form.action.includes('/equipamentos/')) { const origin=document.createElement('input'); origin.type='hidden'; origin.name='retorno'; origin.value='editar'; form.append(origin); }
        });
        document.querySelectorAll('[data-os-catalog]').forEach(input => {
            const hidden = document.getElementById(input.dataset.osId);
            input.addEventListener('input', () => {
                hidden.value = '';
                input.setCustomValidity(input.required ? 'Selecione uma opção da lista de sugestões.' : '');
                if ($) $(hidden).trigger('change');
            });
            if (! $?.fn.autocomplete) return;
            $(input).autocomplete({source:root.dataset.osBase + '/autoComplete' + input.dataset.osCatalog, minLength:1,
                select:function (event, ui) {
                    event.preventDefault();
                    input.value = ui.item.value || ui.item.label;
                    hidden.value = ui.item.id || '';
                    input.setCustomValidity('');
                    const price = document.getElementById(input.dataset.osPrice);
                    if (price) price.value = ui.item.preco ?? '';
                    $(hidden).trigger('change');
                    updateDraft();
                }
            });
        });
        const discount = document.getElementById('formDesconto');
        function previewDiscount() {
            if (!discount) return;
            const total = Number(discount.elements.valorTotal.value);
            const value = Number(discount.elements.desconto.value);
            const percent = discount.elements.tipoDesconto.value === 'porcento';
            const amount = Math.round((percent ? total * value / 100 : value) * 100) / 100;
            const valid = Number.isFinite(value) && value >= 0 && amount <= total && (!percent || value <= 100);
            discount.elements.desconto.setCustomValidity(valid ? '' : 'O desconto deve estar entre zero e o total da OS (até 100%).');
            document.getElementById('discount-preview').textContent = valid ? (total-amount).toLocaleString('pt-BR',{style:'currency',currency:'BRL'}) : 'Confira o desconto informado';
        }
        discount?.addEventListener('input', previewDiscount);
        discount?.addEventListener('change', previewDiscount);
        previewDiscount();
        const paid = document.getElementById('invoice-paid');
        function togglePaid() {
            const fields = document.querySelector('[data-os-paid-fields]');
            if (!paid || !fields) return;
            fields.hidden = !paid.checked;
            fields.querySelectorAll('input,select').forEach(input => { input.disabled = !paid.checked; input.required = paid.checked; });
        }
        paid?.addEventListener('change', togglePaid);
        togglePaid();
        const gateway = document.getElementById('payment-gateway');
        gateway?.addEventListener('change', () => {
            const select = document.getElementById('payment-method');
            const methods = JSON.parse(gateway.selectedOptions[0]?.dataset.methods || '[]');
            select.replaceChildren(new Option('Escolha a forma de pagamento',''));
            methods.forEach(method => select.add(new Option(method.name,method.value)));
            select.disabled = !methods.length;
        });
        document.addEventListener('click', async event => {
            const copy = event.target.closest('[data-os-copy]');
            if (copy) { try { await navigator.clipboard.writeText(copy.dataset.osCopy); notify('Código Pix copiado.'); } catch (_) { notify('Não foi possível copiar. Selecione a chave exibida.', true); } }
            const link = event.target.closest('[data-os-confirm-link]');
            if (link && (mainDirty || !window.confirm(link.dataset.osConfirmLink))) { event.preventDefault(); if (mainDirty) notify('Salve as alterações da OS antes de enviar.', true); }
        });
        document.addEventListener('submit', event => {
            const form = event.target;
            if (!root.contains(form)) return;
            if (busy) { event.preventDefault(); return; }
            updateDraft();
            if (mainDirty && form !== main) {
                event.preventDefault(); event.stopImmediatePropagation();
                notify('Há alterações não salvas no resumo, na análise ou na execução. Use “Salvar alterações da OS” antes desta ação.', true);
                return;
            }
            if (form.dataset.osConfirm && !window.confirm(form.dataset.osConfirm)) { event.preventDefault(); return; }
            if (form === main) {
                const invalidCatalog = Array.from(form.querySelectorAll('[data-os-catalog]')).find(input => input.value && !document.getElementById(input.dataset.osId).value);
                if (invalidCatalog) {
                    event.preventDefault(); open('resumo', false, true);
                    invalidCatalog.setCustomValidity('Selecione uma opção da lista de sugestões.'); invalidCatalog.reportValidity(); return;
                }
            }
            if (!form.matches('[data-os-ajax]')) { navigating = true; busy = true; return; }
            event.preventDefault();
            if (!$) { notify('Recarregue a página para habilitar esta ação.', true); return; }
            busy = true;
            const buttons = Array.from(root.querySelectorAll('button[type="submit"]'));
            buttons.forEach(button => { button.disabled = true; });
            notify('Salvando. Aguarde a confirmação...');
            $.ajax({url:form.action,type:'POST',data:new FormData(form),processData:false,contentType:false,dataType:'json',timeout:90000})
                .done(function (data) {
                    if (form.hasAttribute('data-os-payment') && data?.idCobranca) { navigating=true; location.assign(root.dataset.osBase.replace(/\/os$/, '/cobrancas/visualizar/') + encodeURIComponent(data.idCobranca)); return; }
                    if (data?.result !== true) { notify(typeof data === 'string' ? data.replace(/<[^>]*>/g,'') : (data?.message || data?.messages || data?.mensagem || 'Não foi possível concluir. Confira os dados.'), true); return; }
                    store('mapos.os.feedback', form.dataset.osMessage || 'Alteração salva.');
                    navigating = true; location.reload();
                })
                .fail(function (xhr) {
                    const data = xhr.responseJSON;
                    const message = typeof data === 'string' ? data : data?.message || data?.messages || data?.mensagem;
                    notify(typeof message === 'string' ? message.replace(/<[^>]*>/g,'') : (xhr.status === 403 ? 'A sessão expirou. Recarregue a página antes de continuar.' : 'Não foi possível confirmar o salvamento. Confira os registros antes de tentar novamente.'), true);
                })
                .always(function () { if (navigating) return; busy=false; buttons.forEach(button => { button.disabled=false; }); });
        }, true);
        const saved = stored('mapos.os.feedback');
        if (saved) { store('mapos.os.feedback',''); notify(saved); }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
