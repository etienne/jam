<ul class="actions">
<? if ($this->isLocalizable && (count($_JAM->projectConfig['languages']) > 1)): ?>
<? foreach($_JAM->projectConfig['languages'] as $language): ?>
	<? if ($_JAM->language != $language): ?>
	<li><?= a('admin/'. $this->name .'?a=translate&to='. $language .'&item='. $this->itemID, $_JAM->strings['admin']['translate'] .' ('. $_JAM->strings['languages'][$language] .')') ?></li>
	<? endif; ?>
<? endforeach; ?>
<? endif; ?>
<? if ($this->config['keepVersions'] && $this->item['master']): ?>
	<li id="versions"><?= a('admin/'. $this->name .'?a=old&id='. $this->itemID, $_JAM->strings['admin']['revertLink']) ?></li>
<? endif; ?>
<? if ($this->CanDelete()): ?>
	<li><?= a('admin/'. $this->name .'?a=delete&id='. $this->itemID, $_JAM->strings['admin']['delete'], array('class' => 'delete')) ?></li>
<? endif; ?>
</ul>
<ul class="navigation">
	<li><?= a($linkToListView, $_JAM->strings['admin']['returnToList']) ?></li>
	<? if ($this->itemID): ?>
		<li><?= a($linkToPrevious, '←') ?></li>
		<li><?= a($linkToNext, '→') ?></li>
	<? endif; ?>
</ul>

<? $this->LoadView('form') ?>
