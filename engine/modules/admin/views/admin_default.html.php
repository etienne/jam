<? if ($canInsert): ?>
<?= a('admin/'. $this->name .'?a=form', $indeterminateItem, array('class' => 'button')); ?>
<? else: ?>
<p class="disabled"><?= $_JAM->strings['admin']['cannotInsert'] ?></p>
<? endif; ?>
<? if (Query::TableIsEmpty($this->name)): ?>
<p class="notice"><?= $_JAM->strings['admin']['moduleEmpty'] ?></p>
<? else: ?>
<? $this->LoadView('list'); ?>
<? endif; ?>
