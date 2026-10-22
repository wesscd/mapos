<?php // Explicit form association keeps notification saves independent of the general settings form.?>
<?= form_open('comunicacao/salvar', ['id' => 'notification-config']) ?>
    <input type="hidden" name="acao" value="config">
</form>
<?= form_open('comunicacao/salvar', ['id' => 'notification-recipients']) ?>
    <input type="hidden" name="acao" value="destinatarios">
</form>
<?php foreach ($notificationModels as $model) { ?>
    <?= form_open('comunicacao/salvar', ['id' => 'notification-model-' . (int) $model->id]) ?>
        <input type="hidden" name="acao" value="modelo"><input type="hidden" name="id" value="<?= (int) $model->id ?>">
    </form>
<?php } ?>
