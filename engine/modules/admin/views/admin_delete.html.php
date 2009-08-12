<ul class="actions">
	<li><?= $backLink ?></li>
</ul>

<div class="confirm">
	<p><?= $message ?></p>
	<? $confirmForm->Open() ?>
	<?= $confirmForm->Hidden('module', $this->name) ?>
	<?= $confirmForm->Hidden('master', $this->itemID) ?>
	<?= $confirmForm->Hidden('action', 'delete') ?>
	<?= $confirmForm->Submit('cancel', $_JAM->strings['admin']['cancel']) ?>
	<?= $confirmForm->Submit('delete', $_JAM->strings['admin']['delete']) ?>
	<? $confirmForm->Close() ?>
</div>

<? $this->AutoForm() ?>
