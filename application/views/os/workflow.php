<?php if (isset($workflow) && $result) {
    $canWorkflow = $editavel && ! (int) $result->faturado && ! in_array($result->status, ['Cancelado', 'Faturado'], true); ?>
<section class="mapos-workflow" id="acompanhamento" aria-labelledby="workflow-title">
    <div class="mapos-section-heading">
        <div><span class="mapos-eyebrow">ATENDIMENTO</span><h2 id="workflow-title">Acompanhamento da OS #<?= (int) $result->idOs ?></h2></div>
        <span class="mapos-pill"><?= html_escape($result->status) ?></span>
    </div>
    <p>Responsável: <strong><?= html_escape($result->nome) ?></strong> · Prazo: <strong><?= $result->dataFinal ? html_escape(date('d/m/Y', strtotime($result->dataFinal))) : 'Não definido' ?></strong></p>
    <?php if (! empty($result->entregue_em)) { ?><p>Entrega registrada em <?= html_escape(date('d/m/Y H:i', strtotime($result->entregue_em))) ?>.</p>
    <?php } elseif ((in_array($result->status, ['Finalizado', 'Faturado'], true) || ($result->status === 'Cancelado' && ! empty($workflow['recibos']))) && $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs')) { ?>
        <?= form_open('acompanhamento/salvar/' . (int) $result->idOs) ?><input type="hidden" name="acao" value="entrega"><label>Nome de quem retira <input name="retirado_por" maxlength="150" <?= ! empty($workflow['recibos']) ? 'required' : '' ?>></label><button class="btn btn-primary">Confirmar entrega ao cliente</button></form>
    <?php } ?>
    <?php $this->load->view('equipamentos/os'); ?>
    <div class="mapos-grid">
        <section class="mapos-card"><h3>Checklist técnico</h3>
            <?php foreach ($workflow['checklist'] as $item) { ?>
                <div class="mapos-check">
                    <?= form_open('acompanhamento/salvar/' . (int) $result->idOs) ?>
                    <input type="hidden" name="acao" value="checklist"><input type="hidden" name="item_id" value="<?= (int) $item->id ?>">
                    <input type="hidden" name="concluido" value="<?= $item->concluido ? '0' : '1' ?>">
                    <span><?= $item->concluido ? '✓' : '○' ?> <?= html_escape($item->descricao) ?></span>
                    <?php if ($canWorkflow) { ?><button class="btn btn-small" type="submit"><?= $item->concluido ? 'Reabrir' : 'Concluir' ?></button><?php } ?>
                    </form>
                </div>
            <?php } ?>
            <?php if (! $workflow['checklist']) { ?><p class="mapos-muted">Registre as verificações necessárias para este atendimento.</p><?php } ?>
            <?php if ($canWorkflow) { ?>
                <?= form_open('acompanhamento/salvar/' . (int) $result->idOs, ['class' => 'mapos-inline-form']) ?>
                    <input type="hidden" name="acao" value="checklist">
                    <label for="checklist-description">Nova verificação</label>
                    <input id="checklist-description" name="descricao" maxlength="180" required placeholder="Ex.: testar carregamento">
                    <button class="btn btn-primary" type="submit">Adicionar</button>
                </form>
            <?php } ?>
        </section>
        <section class="mapos-card"><h3>Aprovação do orçamento</h3>
            <p>O cliente responde pela própria área do cliente. Alterações nos itens ou valores exigem uma nova aprovação.</p>
            <?php foreach (array_slice($workflow['aprovacoes'], 0, 3) as $approval) { ?>
                <p><span class="mapos-pill"><?= html_escape(ucfirst($approval->status)) ?></span>
                R$ <?= html_escape(number_format($approval->valor, 2, ',', '.')) ?> · <?= html_escape(date('d/m/Y H:i', strtotime($approval->created_at))) ?></p>
            <?php } ?>
            <?php if ($canWorkflow) { ?>
                <?= form_open('acompanhamento/salvar/' . (int) $result->idOs) ?>
                    <input type="hidden" name="acao" value="orcamento">
                    <button class="btn btn-primary" type="submit">Solicitar aprovação do orçamento atual</button>
                </form>
            <?php } ?>
        </section>
    </div>
    <details class="mapos-card mapos-history"><summary>Histórico do atendimento <span class="mapos-muted">— últimos 100 eventos</span></summary>
        <?php if (! $workflow['eventos']) { ?><p>O histórico começa a registrar eventos a partir desta atualização.</p><?php } ?>
        <ol><?php foreach ($workflow['eventos'] as $event) { ?>
            <li><time><?= html_escape(date('d/m/Y H:i', strtotime($event->created_at))) ?></time> <?= html_escape($event->descricao) ?></li>
        <?php } ?></ol>
    </details>
</section>
<?php } ?>
