(function () {
    'use strict';
    function init() {
        const header = document.querySelector('[data-os-workspace]');
        const tabs = Array.from(document.querySelectorAll('[data-os-tab]'));
        const panels = Array.from(document.querySelectorAll('[data-os-panel]'));
        if (!header || tabs.length !== 6 || panels.length !== 6) return;
        const storageKey = 'mapos.os.tab.' + header.dataset.osWorkspace;
        const aliases = {tab1: 'resumo', tab2: 'orcamento', tab3: 'orcamento', tab4: 'orcamento', tab5: 'historico', tab6: 'historico', equipamentos: 'equipamento', 'equipamentos-saida': 'entrega', acompanhamento: 'resumo'};
        function storedTab() { try { return sessionStorage.getItem(storageKey); } catch (_) { return null; } }
        function open(key, focus, updateUrl) {
            if (!tabs.some(tab => tab.dataset.osTab === key)) key = 'resumo';
            tabs.forEach(tab => {
                const active = tab.dataset.osTab === key;
                tab.setAttribute('aria-selected', String(active));
                tab.tabIndex = active ? 0 : -1;
                if (active && focus) tab.focus();
            });
            panels.forEach(panel => { panel.hidden = panel.dataset.osPanel !== key; });
            try { sessionStorage.setItem(storageKey, key); } catch (_) { /* Tabs also work without storage. */ }
            if (updateUrl) window.history.replaceState(null, '', '#os-pane-' + key);
        }
        // Read-only OS uses the same six sections as editing, retaining the original report fallback.
        const analysisTarget = document.querySelector('[data-os-read-analysis-target]');
        if (analysisTarget) {
            const rows = Array.from(document.querySelectorAll('[data-os-read-analysis]'));
            rows.forEach(row => analysisTarget.append(row));
            const empty = document.querySelector('[data-os-empty-analysis]');
            if (empty) empty.hidden = rows.length > 0;
            ['budget', 'history'].forEach(kind => {
                const source = document.querySelector('[data-os-read-' + kind + ']');
                const target = document.querySelector('[data-os-read-' + kind + '-target]');
                if (source && target) target.append(source);
            });
        }
        const actions = document.querySelector('.os-workspace-card .widget-title .buttons');
        if (actions) {
            const finance = document.createElement('div');
            finance.className = 'os-finance-actions';
            ['btn-faturar', 'btn-forma-pagamento', 'btn-pix'].forEach(id => {
                const button = document.getElementById(id);
                if (button) finance.append(button);
            });
            if (finance.children.length) document.getElementById('os-pane-orcamento').prepend(finance);
            const menu = document.createElement('details');
            menu.className = 'os-actions-menu';
            const label = document.createElement('summary');
            label.textContent = 'Outras ações: impressão e comunicação';
            menu.append(label, actions);
            header.append(menu);
            const oldTitle = document.querySelector('.os-workspace-card .widget-title');
            if (oldTitle) oldTitle.hidden = true;
        }
        document.body.classList.add('os-workspace-ready');
        function fromHash() {
            const hash = window.location.hash.slice(1);
            const key = hash.startsWith('os-pane-') ? hash.slice(8) : aliases[hash];
            open(document.querySelector('[data-os-form-errors]') ? 'resumo' : (key || storedTab() || 'resumo'), false, false);
            if (/^tab[2-6]$/.test(hash) && window.jQuery) window.jQuery('a[href="#' + hash + '"]').tab('show');
        }
        fromHash();
        // Returning from a stage form should keep the overview and navigation in view.
        if (window.location.hash) window.addEventListener('load', () => window.scrollTo(0, 0), {once: true});
        window.addEventListener('hashchange', fromHash);
        document.addEventListener('click', event => {
            const target = event.target.closest('[data-os-tab], [data-os-open]');
            if (!target) return;
            event.preventDefault();
            open(target.dataset.osTab || target.dataset.osOpen, false, true);
        });
        tabs.forEach((tab, index) => tab.addEventListener('keydown', event => {
            let next;
            if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
            if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
            if (event.key === 'Home') next = 0;
            if (event.key === 'End') next = tabs.length - 1;
            if (next === undefined) return;
            event.preventDefault();
            open(tabs[next].dataset.osTab, true, true);
        }));
        document.addEventListener('invalid', event => {
            const panel = event.target.closest('[data-os-panel]');
            if (panel) open(panel.dataset.osPanel, false, false);
            if (event.target.form?.id === 'formOs') open('resumo', false, false);
        }, true);
        if (window.jQuery) {
            const $ = window.jQuery;
            $('#formOs').on('invalid-form.osWorkspace', () => open('resumo', false, false));
            $(document).ajaxSuccess(function (_, xhr, settings, data) {
                if (settings.type?.toUpperCase() !== 'POST' || !data?.result || !/\/os\/(adicionarProduto|adicionarServico|excluirProduto|excluirServico)$/.test(settings.url)) return;
                $.get(window.location.href.split('#')[0], function (html) {
                    const parsed = new DOMParser().parseFromString(html, 'text/html');
                    const facts = parsed.querySelector('[data-os-indicators]');
                    if (facts) document.querySelector('[data-os-indicators]').replaceChildren(...Array.from(facts.childNodes));
                    const next = parsed.querySelector('[data-os-open]');
                    const currentNext = document.querySelector('[data-os-open]');
                    if (next && currentNext) currentNext.replaceWith(next);
                    parsed.querySelectorAll('[data-os-badge]').forEach(badge => {
                        const current = document.querySelector('[data-os-badge="' + badge.dataset.osBadge + '"]');
                        if (current) current.textContent = badge.textContent;
                    });
                });
            });
        }
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
