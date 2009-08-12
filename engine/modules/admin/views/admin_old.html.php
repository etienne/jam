<h2><?= $_JAM->strings['admin']['oldVersions'] ?></h2>
<ul class="versions">
<? foreach ($paths as $path): ?>
	<li><?= $path ?></li>
<? endforeach; ?>
</ul>

<?= a('admin/'. $this->name .'?a=edit&id='. $this->itemID, $_JAM->strings['admin']['cancel'], array('class' => 'button')) ?>
