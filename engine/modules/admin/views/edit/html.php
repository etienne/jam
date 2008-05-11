<ul class="actions">
<? if ($this->config['keepVersions'] && $this->items[$this->itemID]['master']): ?>
	<li><?= a('admin/'. $this->name .'?a=old&id='. $this->itemID, $_JAG['strings']['admin']['revertLink']) ?></li>
<? endif; ?>
	<li><?= a('admin/'. $this->name .'?a=delete&id='. $this->itemID, $_JAG['strings']['admin']['delete']) ?></li>
</ul>

<? $this->LoadView('form') ?>


