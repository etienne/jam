<ul class="actions">
<? if ($this->isLocalizable && (count($_JAG['project']['languages']) > 1)): ?>
<? foreach($_JAG['project']['languages'] as $language): ?>
	<? if ($_JAG['language'] != $language): ?>
	<li><?= a('admin/'. $this->name .'?a=translate&to='. $language .'&item='. $this->itemID, $_JAG['strings']['admin']['translate'] .' ('. $_JAG['strings']['languages'][$language] .')') ?></li>
	<? endif; ?>
<? endforeach; ?>
<? endif; ?>
<? if ($this->config['keepVersions'] && $this->item['master']): ?>
	<li id="versions"><?= a('admin/'. $this->name .'?a=old&id='. $this->itemID, $_JAG['strings']['admin']['revertLink']) ?></li>
<? endif; ?>
<? if ($this->CanDelete()): ?>
	<li><?= a('admin/'. $this->name .'?a=delete&id='. $this->itemID, $_JAG['strings']['admin']['delete']) ?></li>
<? endif; ?>
</ul>

<? $this->LoadView('form') ?>


