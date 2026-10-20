<?php
$this->load->library('vistoria_service');
$prefix = $vistoriaPrefix ?? 'entrada';
$creation = ! empty($vistoriaCreation);
?>
<section class="mapos-inspection"><h3>Estado do equipamento e fotos de entrada</h3>
    <p>Registre riscos, trincas, peças ausentes e condições aparentes. Os ângulos são opcionais; use apenas os que se aplicam ao equipamento.</p>
    <?php if (empty($vistoriaOnlyPhotos)) { ?>
        <label for="<?= html_escape($prefix) ?>-estado">Estado do equipamento</label>
        <select id="<?= html_escape($prefix) ?>-estado" name="vistoria_estado"><option value=""><?= $creation ? 'Não registrar vistoria agora' : 'Selecione o estado observado' ?></option><?php foreach (Vistoria_service::STATES as $value => $label) { ?><option value="<?= html_escape($value) ?>" <?= $creation ? set_select('vistoria_estado', $value) : '' ?>><?= html_escape($label) ?></option><?php } ?></select>
        <label for="<?= html_escape($prefix) ?>-condicoes">Condições e avarias observadas (até 2.000 caracteres)</label>
        <textarea id="<?= html_escape($prefix) ?>-condicoes" name="vistoria_condicoes" rows="3" maxlength="2000" placeholder="Ex.: risco na tampa, canto esquerdo trincado, duas teclas ausentes."><?= $creation ? set_value('vistoria_condicoes') : '' ?></textarea>
        <label for="<?= html_escape($prefix) ?>-acessorios">Acessórios conferidos (informe “Nenhum” se for o caso)</label>
        <textarea id="<?= html_escape($prefix) ?>-acessorios" name="vistoria_acessorios" rows="2" maxlength="2000"><?= $creation ? set_value('vistoria_acessorios') : '' ?></textarea>
    <?php } ?>
    <p>JPEG ou PNG, até 8 MB e 24 megapixels por foto. Use “Usar câmera” para fotografar no celular ou escolha um arquivo da galeria. As imagens são reduzidas para armazenamento.</p>
    <div class="mapos-photo-fields"><?php foreach (Vistoria_service::ANGLES as $angle => $label) { ?>
        <fieldset><legend><?= html_escape($label) ?></legend>
            <label for="<?= html_escape($prefix . '-' . $angle) ?>">Foto · <?= html_escape($label) ?></label>
            <input id="<?= html_escape($prefix . '-' . $angle) ?>" type="file" name="vistoria_foto_<?= html_escape($angle) ?>" accept="image/jpeg,image/png" data-vistoria-upload>
            <button type="button" class="btn btn-small" data-vistoria-camera>Usar câmera · <?= html_escape($label) ?></button>
            <img data-vistoria-preview hidden alt="Prévia da foto de <?= html_escape($label) ?>">
            <label for="<?= html_escape($prefix . '-' . $angle) ?>-obs">Observação · <?= html_escape($label) ?> (até 500 caracteres)</label>
            <input id="<?= html_escape($prefix . '-' . $angle) ?>-obs" name="vistoria_obs_<?= html_escape($angle) ?>" maxlength="500" placeholder="Localize a marca ou dano visível." value="<?= $creation ? set_value('vistoria_obs_' . $angle) : '' ?>">
        </fieldset>
    <?php } ?></div>
    <?php if ($creation) { ?>
        <label><input type="checkbox" name="vistoria_receber" value="1" <?= set_checkbox('vistoria_receber', '1') ?>> Confirmar também que este equipamento foi recebido fisicamente</label>
        <label for="vistoria-entregue-por">Nome de quem entregou à assistência (obrigatório para confirmar recebimento)</label>
        <input id="vistoria-entregue-por" name="vistoria_entregue_por" maxlength="150" value="<?= set_value('vistoria_entregue_por') ?>">
        <p>A vistoria exige um equipamento selecionado. Para registrá-la, preencha estado, condições e acessórios. Sem a confirmação acima, a OS ficará sem recebimento físico confirmado.</p>
    <?php } else { ?><p>O registro preserva o estado e as observações. Antes de confirmar o recebimento, você ainda poderá acrescentar fotos, sem substituir as anteriores.</p><?php } ?>
</section>
