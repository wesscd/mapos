<?php
$osWorkspace = $this->os_workflow->workspaceState($result, $workflow);
$osCanEquipment = $this->permission->checkPermission($this->session->userdata('permissao'), 'vCliente');
$osTabs = ['resumo' => 'Resumo', 'equipamento' => 'Equipamento e vistoria', 'analise' => 'Análise técnica', 'orcamento' => 'Orçamento', 'execucao' => 'Execução e testes', 'entrega' => 'Entrega', 'historico' => 'Histórico e anexos'];
$osBadges = ['equipamento' => $osCanEquipment ? $osWorkspace['equipment'] : 'Permissão necessária', 'analise' => $osWorkspace['analysis'], 'orcamento' => $osWorkspace['budget'], 'execucao' => $osWorkspace['execution'], 'entrega' => $osWorkspace['delivery']];
?>
<header class="os-summary" id="acompanhamento" data-os-workspace="<?= (int) $result->idOs ?>">
    <div class="os-summary-top"><div><span class="mapos-eyebrow">ORDEM DE SERVIÇO</span><h1>OS #<?= (int) $result->idOs ?> <span class="mapos-pill"><?= html_escape($result->status) ?></span></h1><p><?= html_escape($result->nomeCliente) ?> <span class="os-summary-separator">·</span> Responsável: <?= html_escape($result->nome) ?></p></div>
        <a class="os-next-action" href="#os-pane-<?= html_escape($osWorkspace['nextTab']) ?>" data-os-open="<?= html_escape($osWorkspace['nextTab']) ?>"><?= html_escape($osWorkspace['nextLabel']) ?> <span aria-hidden="true">→</span></a>
    </div>
    <div class="os-summary-facts" data-os-indicators>
        <span><strong>Equipamento:</strong> <?= $osCanEquipment && $workflow['equipamentos'] ? html_escape(implode(' · ', array_map(fn ($equipment) => $equipment->equipamento, $workflow['equipamentos']))) : 'Confira a aba Equipamento e vistoria' ?></span>
        <span><strong>Total da OS:</strong> R$ <?= html_escape(number_format($result->tipo_desconto ? $result->valor_desconto : $totalProdutos + $totalServico, 2, ',', '.')) ?></span>
        <?php if ($result->entregue_em) { ?><span>Entrega registrada em <?= html_escape(date('d/m/Y H:i', strtotime($result->entregue_em))) ?></span><?php } ?>
    </div>
<?php $this->load->view('os/actions'); ?>
</header>
<nav class="os-main-tabs" role="tablist" aria-label="Etapas da ordem de serviço">
    <?php foreach ($osTabs as $tabKey => $tabLabel) { ?>
        <a id="os-tab-<?= html_escape($tabKey) ?>" href="#os-pane-<?= html_escape($tabKey) ?>" role="tab" aria-controls="os-pane-<?= html_escape($tabKey) ?>" data-os-tab="<?= html_escape($tabKey) ?>" aria-selected="<?= $tabKey === 'resumo' ? 'true' : 'false' ?>" tabindex="<?= $tabKey === 'resumo' ? '0' : '-1' ?>">
            <span><?= html_escape($tabLabel) ?><?php if ($tabKey === 'equipamento') { ?><small class="os-required">Obrigatória</small><?php } ?></span>
            <?php if (isset($osBadges[$tabKey])) { ?><small data-os-badge="<?= html_escape($tabKey) ?>" class="os-tab-state <?= $tabKey === 'equipamento' && $osWorkspace['equipmentPending'] ? 'is-pending' : '' ?>"><?= html_escape($osBadges[$tabKey]) ?></small><?php } ?>
        </a>
    <?php } ?>
</nav>
