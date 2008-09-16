<? if ($canInsert): ?>
<?= a('admin/'. $this->name .'?a=form', $_JAG['strings']['admin']['newItem'], array('class' => 'button')); ?>
<? else: ?>
<p class="disabled"><?= $_JAG['strings']['admin']['cannotInsert'] ?></p>
<? endif; ?>
<? if (Query::TableIsEmpty($this->name)): ?>
<p class="notice"><?= $_JAG['strings']['admin']['moduleEmpty'] ?></p>
<? else: ?>
<? $this->LoadView('list'); ?>
<? endif; ?>
