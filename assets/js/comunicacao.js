(() => {
    const root = document.getElementById('communication');
    if (!root) return;
    const labels = {
        disconnected: 'Desconectado. Clique em conectar para gerar o QR Code.',
        connecting: 'Conectando ao WhatsApp…', qr: 'Leia o QR Code com o celular.',
        pairing: 'Concluindo o pareamento com o celular. Aguarde; não é necessário ler outro QR Code.',
        disconnecting: 'Encerrando a conexão com o WhatsApp…',
        qr_expired: 'O tempo para ler o QR Code terminou. Clique em conectar para gerar um novo.',
        connected: 'WhatsApp conectado.', reconnecting: 'Conexão interrompida. Tentando reconectar…',
        needs_pairing: 'O pareamento não foi concluído ou a sessão foi encerrada. Clique em conectar para tentar novamente.',
        unavailable: 'Serviço indisponível. Os avisos aguardam na fila.'
    };
    const image = document.getElementById('wa-qr');
    let qrExpiry;
    function clearQr() {
        clearTimeout(qrExpiry);
        image.hidden = true;
        image.removeAttribute('src');
    }
    async function refresh() {
        try {
            const requestedAt = performance.now();
            const response = await fetch(root.dataset.statusUrl, { credentials: 'same-origin', cache: 'no-store', signal: AbortSignal.timeout(10000), headers: { Accept: 'application/json' } });
            if (!response.ok || response.redirected) throw new Error('Unavailable');
            const status = await response.json();
            const qrValidForMs = status.qrValidForMs - (performance.now() - requestedAt);
            document.getElementById('wa-state').textContent = labels[status.state] || labels.unavailable;
            document.getElementById('wa-account').textContent = status.account ? `Número conectado: +${status.account}` : '';
            clearQr();
            if (status.qr && qrValidForMs > 0 && /^data:image\/png;base64,/.test(status.qr)) {
                image.src = status.qr;
                image.hidden = false;
                qrExpiry = setTimeout(clearQr, Math.min(qrValidForMs, 60000));
            }
            const diagnostic = document.getElementById('wa-diagnostic');
            if (diagnostic) diagnostic.textContent = status.lastDisconnect && status.state !== 'connected'
                ? `Última desconexão: código ${status.lastDisconnect.code ?? 'não informado'} · ${new Date(status.lastDisconnect.at).toLocaleString('pt-BR')}` : '';
        } catch {
            document.getElementById('wa-state').textContent = labels.unavailable;
            clearQr();
        } finally {
            setTimeout(refresh, 5000);
        }
    }
    refresh();
})();
