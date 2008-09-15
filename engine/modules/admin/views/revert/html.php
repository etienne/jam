<ul class="actions">
	<li><?= $backLink ?></li>
</ul>

<div class="confirm">
	<p><?= $message ?></p>
	<? $confirmForm->Open() ?>
	<?= $confirmForm->Hidden('module', $this->name) ?>
	<?= $confirmForm->Hidden('revertID', $revertID) ?>
	<?= $confirmForm->Hidden('master', $masterID) ?>
	<?= $confirmForm->Hidden('action', 'revert') ?>
	<?= $confirmForm->Submit('cancel', $_JAG['strings']['admin']['cancel']) ?>
	<?= $confirmForm->Submit('revert', $_JAG['strings']['admin']['revert']) ?>
	<? $confirmForm->Close() ?>
</div>

<? $this->AutoForm() ?>
