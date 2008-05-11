<?= a('admin/'. $this->name .'?a=form', $_JAG['strings']['admin']['newItem'], array('class' => 'button')); ?>
<? if (Query::TableIsEmpty($this->name)): ?>
<p class="error"><?= $_JAG['strings']['admin']['moduleEmpty'] ?></p>
<? else: ?>
<? $this->LoadView('list'); ?>
<? endif; ?>
