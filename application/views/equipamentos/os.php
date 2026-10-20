<?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vCliente')) {
    $canEquipment = $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs'); ?>
<section id="equipamentos" class="mapos-card"><h3>Equipamentos e recebimento físico</h3>
    <p>Vincular um cadastro não confirma que o equipamento chegou à assistência. Registre a entrada após conferir o item e os acessórios.</p>
    <?php foreach ($workflow['equipamentos'] as $equipment) {
        $receipts = array_filter($workflow['recibos'], fn ($receipt) => (int) $receipt->vinculo_id === (int) $equipment->idEquipamentos_os); ?>
        <article class="mapos-card"><h4><a href="<?= site_url('equipamentos/visualizar/' . (int) $equipment->idEquipamentos) ?>">#<?= (int) $equipment->idEquipamentos ?> · <?= html_escape($equipment->equipamento) ?></a></h4>
            <p><?= html_escape($equipment->modelo) ?> · Série: <?= html_escape($equipment->num_serie ?: 'Não informada') ?></p>
            <?php $this->load->library('vistoria_service');
        $inspection = $this->vistoria_service->get($equipment->idEquipamentos_os);
        if ($inspection) {
            $this->load->view('equipamentos/vistoria_resumo', ['inspection' => $inspection]);
        }
        if (! $receipts && $canEquipment && ! $result->entregue_em && $result->status !== 'Cancelado') { ?>
                <details><summary><?= $inspection ? 'Acrescentar fotos à vistoria' : 'Registrar estado e fotos na entrada' ?></summary>
                    <?= form_open_multipart('equipamentos/vistoria/' . (int) $result->idOs) ?>
                        <input type="hidden" name="vinculo_id" value="<?= (int) $equipment->idEquipamentos_os ?>">
                        <?php if ($inspection) { ?><input type="hidden" name="complementar" value="1"><?php } ?>
                        <?php $this->load->view('equipamentos/vistoria_campos', ['vistoriaPrefix' => 'vistoria-' . (int) $equipment->idEquipamentos_os, 'vistoriaCreation' => false, 'vistoriaOnlyPhotos' => (bool) $inspection]); ?>
                        <button class="btn btn-primary"><?= $inspection ? 'Acrescentar fotos' : 'Registrar vistoria' ?></button>
                    </form>
                </details>
            <?php } ?>
            <?php foreach ($receipts as $receipt) { ?><p><a target="_blank" rel="noopener" href="<?= site_url('equipamentos/comprovante/' . (int) $receipt->id) ?>">Comprovante de <?= $receipt->tipo === 'entrada' ? 'recebimento' : 'entrega' ?> #<?= (int) $receipt->id ?></a> · <?= html_escape(date('d/m/Y H:i', strtotime($receipt->created_at))) ?></p><?php } ?>
            <?php if (! $receipts) { ?><p><strong>Recebimento físico não registrado.</strong></p>
                <?php if ($canEquipment && ! $result->entregue_em && $result->status !== 'Cancelado') { ?>
                <details><summary>Registrar recebimento físico</summary>
                    <p>Confira a vistoria e acrescente as fotos necessárias antes de confirmar. O comprovante preservará os registros disponíveis neste momento.</p>
                    <?= form_open('equipamentos/receber/' . (int) $result->idOs) ?><input type="hidden" name="vinculo_id" value="<?= (int) $equipment->idEquipamentos_os ?>">
                        <label>Nome de quem entrega o equipamento <input name="entregue_por" maxlength="150" required></label>
                        <label>Acessórios recebidos (informe “Nenhum” quando aplicável)<textarea name="acessorios" maxlength="2000" required><?= html_escape($inspection['acessorios'] ?? '') ?></textarea></label>
                        <label>Condições de entrada: marcas, danos e funcionamento aparente<textarea name="condicoes" maxlength="2000" required><?= html_escape($inspection['condicoes'] ?? '') ?></textarea></label>
                        <button class="btn btn-primary">Confirmar recebimento e gerar comprovante</button>
                    </form>
                </details><?php } ?>
            <?php } ?>
        </article>
    <?php } ?>
    <?php if ($canEquipment && ! $result->faturado && ! $result->entregue_em && ! in_array($result->status, ['Cancelado', 'Faturado'], true)) {
        $available = $workflow['equipamentos_disponiveis']; ?>
        <?= form_open('equipamentos/vincular/' . (int) $result->idOs) ?><label>Equipamento deste cliente<select name="equipamento_id" required><option value="">Selecione um equipamento</option><?php foreach ($available as $equipment) { ?><option value="<?= (int) $equipment->idEquipamentos ?>">#<?= (int) $equipment->idEquipamentos ?> · <?= html_escape($equipment->equipamento . ' · ' . $equipment->num_serie) ?></option><?php } ?></select></label><button class="btn">Vincular à OS</button></form>
        <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'aCliente')) { ?><p><a href="<?= site_url('equipamentos/salvar') . '?cliente=' . (int) $result->clientes_id ?>">Cadastrar novo equipamento para este cliente</a></p><?php } ?>
        <p class="mapos-muted">Seleção mostra até 200 cadastros recentes do cliente.</p>
    <?php } ?>
</section>
<?php } ?>
