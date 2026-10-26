<div class="os-text-fields"><label for="<?= html_escape($noteField) ?>"><?= html_escape($noteLabel) ?></label>
<?php if ($osEditing) {
    $posted = $this->input->post($noteField); ?>
    <textarea class="editor" form="formOs" id="<?= html_escape($noteField) ?>" name="<?= html_escape($noteField) ?>" rows="6"><?= html_escape(is_string($posted) ? $posted : (string) $result->$noteField) ?></textarea>
    <div class="os-form-actions"><button type="submit" class="btn btn-primary" form="formOs">Salvar dados da OS</button></div>
<?php } else { ?><div class="os-prose"><?= nl2br(html_escape(trim(strip_tags((string) $result->$noteField)))) ?: 'Não informado.' ?></div><?php } ?>
</div>
