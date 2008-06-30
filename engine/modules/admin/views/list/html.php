<? if ($items): ?>
<ul class="items">
<? foreach ($items as $item): ?>
	<li><?= a('admin/'. $this->name .'?a=edit&id='. $item[$idColumn], $item[$keyColumn]) ?></li>
<? endforeach; ?>
</ul>
<? else: ?>
<p class="error"><?= $_JAG['strings']['admin']['moduleEmptyForThisLanguage'] ?></p>
<? endif; ?>