(() => {
    const root = document.getElementById('communication');
    if (!root) return;
    const labels = {
        disconnected: 'Desconectado. Clique em conectar para gerar o QR Code.',
        connecting: 'Conectando ao WhatsApp…', qr: 'Leia o QR Code com o celular.',
        connected: 'WhatsApp conectado.', reconnecting: 'Conexão interrompida. Tentando reconectar…',
        needs_pairing: 'Sessão encerrada. Desconecte e conecte novamente para gerar outro QR Code.',
        unavailable: 'Serviço indisponível. Os avisos aguardam na fila.'
    };
    const image = document.getElementById('wa-qr');
    async function refresh() {
        try {
            const response = await fetch(root.dataset.statusUrl, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } });
            if (!response.ok || response.redirected) throw new Error('Unavailable');
            const status = await response.json();
            document.getElementById('wa-state').textContent = labels[status.state] || labels.unavailable;
            document.getElementById('wa-account').textContent = status.account ? `Número conectado: +${status.account}` : '';
            image.hidden = !status.qr;
            if (status.qr && /^data:image\/png;base64,/.test(status.qr)) image.src = status.qr;
            else image.removeAttribute('src');
        } catch {
            document.getElementById('wa-state').textContent = labels.unavailable;
            image.hidden = true;
            image.removeAttribute('src');
        } finally {
            setTimeout(refresh, 5000);
        }
    }
    refresh();
})();
