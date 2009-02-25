<? if ($items): ?>
<div>
<label for="cave"><?= $this->strings['adminTitle'] ?></label>
<div>
<ul>
<? foreach ($items as $id => $item): ?>
	<li>
		<?= a('admin/'. $this->name .'?a=edit&id='. $id, next($item)) ?>
	</li>
<? endforeach; ?>
</ul>
</div>
</div>
<? else: ?>
<?= $_JAG['strings']['admin']['na'] ?>
<? endif; ?>