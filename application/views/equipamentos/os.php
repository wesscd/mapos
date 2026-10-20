<?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'vCliente')) {
    $equipmentPhase = $equipmentPhase ?? 'entrada';
    $canEquipment = $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs'); ?>
<section id="<?= $equipmentPhase === 'saida' ? 'equipamentos-saida' : 'equipamentos' ?>" class="mapos-card"><h3><?= $equipmentPhase === 'saida' ? 'Conferência de saída por equipamento' : 'Equipamentos e recebimento físico' ?></h3>
    <?php if ($equipmentPhase !== 'saida') { ?><p>Etapa obrigatória nos atendimentos com equipamento físico. Vincular um cadastro não confirma o recebimento: confira o item, os acessórios e o estado de entrada.</p><p class="mapos-muted">Para atendimento sem recebimento físico, mantenha a OS sem entrada confirmada. A abertura pelo portal também não confirma a chegada do equipamento.</p><?php } ?>
    <?php if (! $workflow['equipamentos']) { ?><p>Nenhum equipamento vinculado a esta OS.</p><?php } ?>
    <?php foreach ($workflow['equipamentos'] as $equipment) {
        $receipts = array_filter($workflow['recibos'], fn ($receipt) => (int) $receipt->vinculo_id === (int) $equipment->idEquipamentos_os); ?>
        <article class="mapos-card"><h4><a href="<?= site_url('equipamentos/visualizar/' . (int) $equipment->idEquipamentos) ?>">#<?= (int) $equipment->idEquipamentos ?> · <?= html_escape($equipment->equipamento) ?></a></h4>
            <p><?= html_escape($equipment->modelo) ?> · Série: <?= html_escape($equipment->num_serie ?: 'Não informada') ?></p>
            <?php $this->load->library('vistoria_service');
        $inspection = $this->vistoria_service->get($equipment->idEquipamentos_os);
        if ($inspection && $equipmentPhase !== 'saida') {
            $this->load->view('equipamentos/vistoria_resumo', ['inspection' => $inspection]);
        }
        if ($equipmentPhase !== 'saida' && ! $receipts && $canEquipment && ! $result->entregue_em && $result->status !== 'Cancelado') { ?>
                <details><summary><?= $inspection ? 'Acrescentar fotos à vistoria' : 'Registrar estado e fotos na entrada' ?></summary>
                    <?= form_open_multipart('equipamentos/vistoria/' . (int) $result->idOs) ?>
                        <input type="hidden" name="vinculo_id" value="<?= (int) $equipment->idEquipamentos_os ?>">
                        <?php if ($inspection) { ?><input type="hidden" name="complementar" value="1"><?php } ?>
                        <?php $this->load->view('equipamentos/vistoria_campos', ['vistoriaPrefix' => 'vistoria-' . (int) $equipment->idEquipamentos_os, 'vistoriaCreation' => false, 'vistoriaOnlyPhotos' => (bool) $inspection, 'vistoriaSaida' => false]); ?>
                        <button class="btn btn-primary"><?= $inspection ? 'Acrescentar fotos' : 'Registrar vistoria' ?></button>
                    </form>
                </details>
            <?php } ?>
            <?php foreach ($receipts as $receipt) {
                if ($equipmentPhase !== 'saida' && $receipt->tipo === 'saida') {
                    continue;
                } ?><p><a target="_blank" rel="noopener" href="<?= site_url('equipamentos/comprovante/' . (int) $receipt->id) ?>">Comprovante de <?= $receipt->tipo === 'entrada' ? 'recebimento' : 'entrega' ?> #<?= (int) $receipt->id ?></a> · <?= html_escape(date('d/m/Y H:i', strtotime($receipt->created_at))) ?></p><?php } ?>
            <?php if (! $receipts && $equipmentPhase !== 'saida') { ?><p><strong>Recebimento físico não registrado.</strong></p>
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
            <?php
                $entryReceipt = current(array_filter($receipts, fn ($receipt) => $receipt->tipo === 'entrada'));
        if ($entryReceipt && $equipmentPhase === 'saida') {
            $entryData = $this->db->select('dados')->get_where('equipamento_recibos', ['id' => $entryReceipt->id])->row();
            $entrySnapshot = json_decode($entryData->dados, true);
            $exitInspection = $this->vistoria_service->get($equipment->idEquipamentos_os, 'saida'); ?>
                <section class="mapos-delivery-review"><h4>Conferência para entrega</h4>
                    <p>Entrada: <?= html_escape($entrySnapshot['condicoes']) ?><br>Acessórios recebidos: <?= html_escape($entrySnapshot['acessorios']) ?></p>
                    <?php if ($exitInspection) {
                        $this->load->view('equipamentos/vistoria_resumo', ['inspection' => $exitInspection, 'inspectionPrint' => false]);
                        $this->load->view('equipamentos/comparacao', ['entryInspection' => $entrySnapshot['vistoria'] ?? null, 'exitInspection' => $exitInspection, 'entryPhotos' => $entrySnapshot['fotos'] ?? []]);
                    } elseif ($result->entregue_em) { ?><p>Entrega já registrada; não há vistoria de saída neste histórico.</p><?php } else { ?><p><strong>Vistoria de saída ainda não registrada.</strong> Confira as condições e os acessórios antes da retirada.</p><?php } ?>
                    <?php if ($canEquipment && ! $result->entregue_em) { ?>
                        <details><summary><?= $exitInspection ? 'Acrescentar fotos de saída' : 'Registrar vistoria de saída' ?></summary>
                            <?= form_open_multipart('equipamentos/vistoria/' . (int) $result->idOs) ?>
                                <input type="hidden" name="vinculo_id" value="<?= (int) $equipment->idEquipamentos_os ?>"><input type="hidden" name="tipo" value="saida">
                                <?php if ($exitInspection) { ?><input type="hidden" name="complementar" value="1"><?php } ?>
                                <?php $this->load->view('equipamentos/vistoria_campos', ['vistoriaPrefix' => 'saida-' . (int) $equipment->idEquipamentos_os, 'vistoriaCreation' => false, 'vistoriaOnlyPhotos' => (bool) $exitInspection, 'vistoriaSaida' => true]); ?>
                                <button class="btn btn-primary"><?= $exitInspection ? 'Acrescentar fotos de saída' : 'Salvar vistoria de saída' ?></button>
                            </form>
                        </details>
                    <?php } ?>
                </section>
            <?php } ?>
        </article>
    <?php } ?>
    <?php if ($equipmentPhase !== 'saida' && $canEquipment && ! $result->faturado && ! $result->entregue_em && ! in_array($result->status, ['Cancelado', 'Faturado'], true)) {
        $available = $workflow['equipamentos_disponiveis']; ?>
        <?= form_open('equipamentos/vincular/' . (int) $result->idOs) ?><label>Equipamento deste cliente<select name="equipamento_id" required><option value="">Selecione um equipamento</option><?php foreach ($available as $equipment) { ?><option value="<?= (int) $equipment->idEquipamentos ?>">#<?= (int) $equipment->idEquipamentos ?> · <?= html_escape($equipment->equipamento . ' · ' . $equipment->num_serie) ?></option><?php } ?></select></label><button class="btn">Vincular à OS</button></form>
        <?php if ($this->permission->checkPermission($this->session->userdata('permissao'), 'aCliente')) { ?><p><a href="<?= site_url('equipamentos/salvar') . '?cliente=' . (int) $result->clientes_id ?>">Cadastrar novo equipamento para este cliente</a></p><?php } ?>
        <p class="mapos-muted">Seleção mostra até 200 cadastros recentes do cliente.</p>
    <?php } ?>
</section>
<?php } ?>
<?php if (! $this->permission->checkPermission($this->session->userdata('permissao'), 'vCliente')) { ?><p class="mapos-card">Você precisa de permissão para consultar clientes e conferir equipamentos. Solicite o acesso ao responsável pelo sistema.</p><?php } ?>
