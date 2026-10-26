(function () {
    'use strict';
    function init() {
        const form = document.getElementById('report-download');
        const $ = window.jQuery;
        if (!form || !$) return;
        const button = form.querySelector('button[type="submit"]');
        const status = document.getElementById('report-download-status');
        let busy = false;
        // jQuery 1.12's default transport reads responseText, which is invalid for blobs.
        $.ajaxTransport('reportpdf', function (options) {
            let request;
            return {
                send:function (headers, complete) {
                    request = new XMLHttpRequest();
                    request.open('POST', options.url, true);
                    Object.keys(headers).forEach(name => request.setRequestHeader(name, headers[name]));
                    request.responseType = 'blob';
                    request.onload = function () {complete(request.status, request.statusText, {reportpdf:request.response}, request.getAllResponseHeaders());};
                    request.onerror = function () {complete(0, 'error');};
                    request.send(options.data);
                },
                abort:function () {if (request) request.abort();}
            };
        });
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (busy) return;
            busy = true;
            button.disabled = true;
            status.hidden = false;
            status.textContent = 'Preparando o PDF. Aguarde...';
            // The shared AJAX prefilter refreshes every form's CSRF token after download.
            $.ajax({url:form.action,type:'POST',data:new FormData(form),processData:false,contentType:false,dataType:'reportpdf',timeout:90000})
                .done(function (blob, textStatus, xhr) {
                    if (!xhr.getResponseHeader('Content-Type')?.startsWith('application/pdf')) {
                        status.textContent = 'Não foi possível gerar o documento. Prepare uma nova prévia.';
                        return;
                    }
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'relatorio-tecnico-os-' + form.dataset.reportOs + '.pdf';
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    setTimeout(() => URL.revokeObjectURL(url), 60000);
                    status.textContent = 'PDF baixado. Você pode ajustar o conteúdo ou baixar novamente.';
                })
                .fail(function (xhr) {
                    status.textContent = xhr.status === 409 ? 'A prévia expirou ou os registros da OS mudaram. Volte e gere uma nova prévia.' : (xhr.status === 403 ? 'A sessão precisa ser atualizada. Reabra a preparação do relatório.' : 'Não foi possível confirmar o download. Tente novamente ou prepare uma nova prévia.');
                })
                .always(function () {busy = false;button.disabled = false;});
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
