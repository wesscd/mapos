<?php $canWorkflow = $osCanMutate; ?>
        <section class="mapos-card"><h3>Checklist técnico</h3>
            <?php foreach ($workflow['checklist'] as $item) { ?>
                <div class="mapos-check">
                    <?= form_open('acompanhamento/salvar/' . (int) $result->idOs) ?><input type="hidden" name="retorno" value="<?= ! empty($osEditing) ? 'editar' : 'visualizar' ?>">
                    <input type="hidden" name="acao" value="checklist"><input type="hidden" name="item_id" value="<?= (int) $item->id ?>">
                    <input type="hidden" name="concluido" value="<?= $item->concluido ? '0' : '1' ?>">
                    <span><?= $item->concluido ? '✓' : '○' ?> <?= html_escape($item->descricao) ?></span>
                    <?php if ($canWorkflow) { ?><button class="btn btn-small" type="submit"><?= $item->concluido ? 'Reabrir' : 'Concluir' ?></button><?php } ?>
                    </form>
                </div>
            <?php } ?>
            <?php if (! $workflow['checklist']) { ?><p class="mapos-muted">Registre as verificações necessárias para este atendimento.</p><?php } ?>
            <?php if ($canWorkflow) { ?>
                <?= form_open('acompanhamento/salvar/' . (int) $result->idOs, ['class' => 'mapos-inline-form']) ?><input type="hidden" name="retorno" value="<?= ! empty($osEditing) ? 'editar' : 'visualizar' ?>">
                    <input type="hidden" name="acao" value="checklist">
                    <label for="checklist-description">Nova verificação</label>
                    <input id="checklist-description" name="descricao" maxlength="180" required placeholder="Ex.: testar carregamento">
                    <button class="btn btn-primary" type="submit">Adicionar</button>
                </form>
            <?php } ?>
        </section>
