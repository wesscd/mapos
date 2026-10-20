<section class="mapos-card"><h3>Retirada e comprovantes</h3>
    <?php if (! empty($result->entregue_em)) { ?><p>Entrega registrada em <?= html_escape(date('d/m/Y H:i', strtotime($result->entregue_em))) ?>.</p>
    <?php } elseif ((in_array($result->status, ['Finalizado', 'Faturado'], true) || ($result->status === 'Cancelado' && ! empty($workflow['recibos']))) && $this->permission->checkPermission($this->session->userdata('permissao'), 'eOs') && (empty($workflow['equipamentos']) || $this->permission->checkPermission($this->session->userdata('permissao'), 'vCliente'))) { ?>
        <?php if (! empty($workflow['equipamentos'])) { ?><p>Antes de confirmar, registre a vistoria de saída de cada equipamento na seção abaixo. O comprovante preservará as fotos, os acessórios e as ressalvas.</p><?php } ?>
        <?= form_open('acompanhamento/salvar/' . (int) $result->idOs) ?><input type="hidden" name="acao" value="entrega"><label>Nome de quem retira <input name="retirado_por" maxlength="150" <?= ! empty($workflow['equipamentos']) ? 'required' : '' ?>></label>
        <?php if (! empty($workflow['equipamentos'])) { ?><label><input type="checkbox" name="aceite_entrega" value="1" required> Quem retira conferiu o equipamento, os acessórios e as ressalvas de todos os itens e confirmou a retirada.</label><p>Marque somente após a conferência presencial. Este aceite é registrado pela equipe; a assinatura pode ser coletada no comprovante.</p><?php } ?>
        <button class="btn btn-primary">Confirmar entrega ao cliente</button></form>
    <?php } else { ?><p>A confirmação da retirada fica disponível quando a OS estiver finalizada, faturada ou cancelada com equipamento recebido. Confira também sua permissão de edição e acesso aos equipamentos.</p><?php } ?>

</section>
<?php $this->load->view('equipamentos/os', ['equipmentPhase' => 'saida']); ?>
