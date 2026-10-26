<?php $review = $this->os_workflow->deliveryReview($workflow); ?>
<section class="mapos-card os-delivery-review" aria-labelledby="delivery-review-title">
    <h2 id="delivery-review-title"><?= $result->entregue_em ? 'Registros da conferência' : 'Conferência antes da entrega' ?></h2>
    <p>Este resumo orienta a revisão do atendimento e não bloqueia a retirada. As regras de recebimento, vistoria e entrega continuam valendo. Responder ao checklist não confirma a entrega nem a aprovação do cliente.</p>
    <?php if ($result->entregue_em) { ?><p>A entrega já foi registrada. Os itens abaixo mostram o histórico disponível.</p><?php } ?>
    <div class="os-review-grid">
    <?php foreach (['analise' => 'Análise técnica', 'execucao' => 'Execução e testes'] as $phase => $label) {
        $summary = $review['fases'][$phase]; ?>
        <article><h3><?= html_escape($label) ?></h3>
        <?php if (! $summary['total']) { ?><p>Nenhum item de checklist aplicado nesta etapa. Isso não confirma que os testes foram realizados.</p>
        <?php } else { ?><p><strong><?= (int) $summary['pendente'] ?> pendente(s)</strong> · <?= (int) $summary['atencao'] ?> para revisar · <?= (int) $summary['total'] ?> no total.</p>
        <?php if ($summary['itens']) { ?><details><summary>Consultar itens pendentes e ressalvas</summary><ul><?php foreach ($summary['itens'] as $item) { ?><li><?= html_escape($item['equipamento']) ?> · <?= html_escape($item['descricao']) ?> — <?= html_escape($item['resultado']) ?></li><?php } ?></ul></details>
        <?php } else { ?><p>Sem pendências registradas nesta etapa. Confira os resultados e o escopo antes da retirada.</p><?php } ?><?php } ?>
        <a class="btn" href="#os-pane-<?= $phase ?>" data-os-open="<?= $phase ?>">Conferir <?= $phase === 'execucao' ? 'execução e testes' : 'análise técnica' ?></a></article>
    <?php } ?>
    </div>
    <h3>Equipamentos e acessórios</h3>
    <?php if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vCliente')) { ?><p>É necessária permissão para consultar os equipamentos e os acessórios.</p>
    <?php } elseif (! $review['equipamentos']) { ?><p>Nenhum equipamento vinculado. Este resumo não indica recebimento ou conferência física.</p>
    <?php } else { ?>
        <?php foreach ($review['equipamentos'] as $equipment) { ?><article class="os-review-equipment"><h4><?= html_escape($equipment['nome']) ?></h4>
        <?php if (! $equipment['recebido']) { ?><p>Recebimento físico não registrado. Confira a entrada na aba Equipamento e vistoria.</p>
        <?php } else { ?><p><strong>Acessórios registrados no recebimento:</strong> <?= nl2br(html_escape($equipment['acessorios'] ?: 'Não informado no comprovante.')) ?></p>
        <p><strong>Conferência de saída:</strong> <?= ! $equipment['saida'] ? 'Vistoria ainda não registrada.' : ($equipment['conferencia'] === 'conferidos' ? 'Acessórios conferidos na vistoria.' : ($equipment['conferencia'] === 'divergencia' ? 'Há divergência registrada. Consulte as ressalvas abaixo.' : 'Resultado da conferência não informado.')) ?></p><?php } ?></article><?php } ?>
        <p>Confira as condições, os acessórios e as ressalvas na seção de saída abaixo e confirme a retirada somente após a conferência presencial.</p>
    <?php } ?>
</section>
