<? if ($originalData): ?>
<dl id="translate_original">
	<? foreach ($localizableFields as $field): ?>
	<? if (is_string($originalData[$field]) || is_int($originalData[$field])): ?>
	<dt><?= $this->strings['fields'][$field] ?></dt>
	<dd><?= $originalData[$field] ?></dd>
	<? endif; ?>
	<? endforeach; ?>
</dl>
<? endif; ?>

<? $this->AutoForm($localizableFields, array('language' => $_GET['to'])); ?>
