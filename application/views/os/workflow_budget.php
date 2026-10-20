<?php $canWorkflow = $osCanMutate; ?>
        <section class="mapos-card" id="os-budget"><h3>Aprovação do orçamento</h3>
            <p>O cliente responde pela própria área do cliente. Alterações nos itens ou valores exigem uma nova aprovação.</p>
            <p>O status “Aprovado” selecionado pela equipe é separado da resposta do cliente registrada abaixo.</p>
            <?php foreach (array_slice($workflow['aprovacoes'], 0, 3) as $approval) { ?>
                <p><span class="mapos-pill"><?= html_escape(ucfirst($approval->status)) ?></span>
                R$ <?= html_escape(number_format($approval->valor, 2, ',', '.')) ?> · <?= html_escape(date('d/m/Y H:i', strtotime($approval->created_at))) ?></p>
            <?php } ?>
            <?php if ($canWorkflow) { ?>
                <?= form_open('acompanhamento/salvar/' . (int) $result->idOs) ?><input type="hidden" name="retorno" value="<?= ! empty($osEditing) ? 'editar' : 'visualizar' ?>">
                    <input type="hidden" name="acao" value="orcamento">
                    <button class="btn btn-primary" type="submit">Solicitar aprovação do orçamento atual</button>
                </form>
            <?php } ?>
        </section>
