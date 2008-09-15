<h2><?= $_JAG['strings']['admin']['oldVersions'] ?></h2>
<ul class="versions">
<? foreach ($paths as $path): ?>
	<li><?= $path ?></li>
<? endforeach; ?>
</ul>

<?= a('admin/'. $this->name .'?a=edit&id='. $this->itemID, $_JAG['strings']['admin']['cancel'], array('class' => 'button')) ?>
