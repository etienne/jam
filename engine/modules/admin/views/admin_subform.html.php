<div>
<label for="subform"><?= $this->strings['adminTitle'] ?></label>
<div>
<? if ($items): ?>
	<ul>
	<? foreach ($items as $id => $item): ?>
		<li>
			<?= a('admin/'. $this->name .'?a=edit&id='. $id, next($item)) ?>
		</li>
	<? endforeach; ?>
	</ul>
<? else: ?>
	<?= $_JAM->strings['admin']['na'] ?>
<? endif; ?>
</div>
</div>
