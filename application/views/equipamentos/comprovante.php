<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Comprovante #<?= (int) $receipt->id ?></title>
<style>.mapos-inspection-photos{display:flex;flex-wrap:wrap;gap:12px}.mapos-inspection-photos figure{width:220px;margin:12px 0;break-inside:avoid}.mapos-inspection-photos img{width:100%;max-height:180px;object-fit:contain}.mapos-inspection-photos figcaption{font-size:12px}body{font:16px/1.5 Arial,sans-serif;color:#182333;max-width:850px;margin:32px auto;padding:20px}h1{font-size:25px}dt{font-weight:bold;margin-top:14px}dd{margin-left:0;white-space:pre-wrap;overflow-wrap:anywhere}.photos{display:flex;flex-wrap:wrap;gap:12px}.photos img{width:220px;max-height:180px;object-fit:contain}.signature{margin-top:70px;border-top:1px solid;padding-top:8px;max-width:400px}button{padding:10px 18px;cursor:pointer}@media print{body{margin:0;max-width:none}button{display:none}img,.signature{break-inside:avoid}@page{size:A4;margin:18mm}}</style></head><body>
    <button type="button" onclick="window.print()">Imprimir / salvar PDF</button>
    <h1>Comprovante de <?= $receipt->tipo === 'entrada' ? 'recebimento do equipamento' : 'entrega do equipamento' ?></h1>
    <p>Registro #<?= (int) $receipt->id ?> · Atendimento #<?= (int) $snapshot['os'] ?> · <?= html_escape(date('d/m/Y H:i', strtotime($receipt->created_at))) ?></p>
    <?php if (! empty($snapshot['empresa'])) { ?><p><?= html_escape($snapshot['empresa']['nome'] ?? '') ?> · <?= html_escape($snapshot['empresa']['cnpj'] ?? '') ?> · <?= html_escape($snapshot['empresa']['telefone'] ?? '') ?></p><?php } ?>
    <dl><dt>Cliente</dt><dd><?= html_escape($snapshot['cliente']) ?></dd>
        <dt>Equipamento #<?= (int) $snapshot['equipamento_id'] ?></dt><dd><?= html_escape($snapshot['equipamento'] . ' · ' . $snapshot['marca'] . ' ' . $snapshot['modelo']) ?></dd>
        <dt>Número de série / cor</dt><dd><?= html_escape(($snapshot['serie'] ?: 'Série não informada') . ' · ' . $snapshot['cor']) ?></dd>
        <dt>Acessórios registrados na entrada</dt><dd><?= html_escape($snapshot['acessorios']) ?></dd>
        <dt>Condições registradas na entrada</dt><dd><?= html_escape($snapshot['condicoes']) ?></dd>
        <?php if ($receipt->tipo === 'entrada') { ?><dt>Equipamento entregue à assistência por</dt><dd><?= html_escape($snapshot['entregue_por']) ?></dd>
        <?php } else { ?><dt>Equipamento retirado por</dt><dd><?= html_escape($snapshot['retirado_por']) ?></dd><dt>Comprovante de entrada</dt><dd>#<?= (int) $snapshot['comprovante_entrada'] ?></dd><?php } ?>
    </dl>
    <?php if ($receipt->tipo === 'entrada' && ! empty($snapshot['fotos'])) { ?><h2>Fotos disponíveis no recebimento</h2><div class="photos"><?php foreach ($snapshot['fotos'] as $photo) { ?><img src="<?= site_url('equipamentos/foto/' . (int) $photo) ?>" alt="Foto de entrada #<?= (int) $photo ?>"><?php } ?></div><?php } ?>
    <?php if (! empty($snapshot['vistoria'])) {
        $this->load->library('vistoria_service');
        $this->load->view('equipamentos/vistoria_resumo', ['inspection' => $snapshot['vistoria'], 'inspectionPrint' => true]);
    } ?>
    <p>Este documento registra a movimentação física informada pela equipe. A assinatura abaixo deve ser coletada no momento da conferência.</p>
    <div class="signature">Assinatura de quem <?= $receipt->tipo === 'entrada' ? 'entrega à assistência' : 'retira o equipamento' ?></div><div class="signature">Responsável pela assistência</div>
</body></html>
