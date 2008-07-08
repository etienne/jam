<? if ($items): ?>
<? switch ($keyColumnType):
	case 'file': ?>

<ul class="images">
<? foreach ($items as $item): ?>
	<? $file = $item[$keyColumn]; ?>
	<? switch ($file->item['type']) :
		case 'image/jpeg':
		case 'image/png':
		case 'image/gif': ?>
			<li><?= a($editLinkPrefix . $item[$idColumn], i($file->item['path'] . '?context=adminList', $file->item['filename'])) ?></li>
			<? break; ?>
		<? default: ?>
			<li><?= a($editLinkPrefix . $item[$idColumn], i('assets/images/admin_bigfile.png', $file->item['filename'])) ?></li>
			<? break; ?>
	<? endswitch; ?>
<? endforeach; ?>
</ul>

<? break; ?>
<? default: ?>

<ul class="items">
<? foreach ($items as $item): ?>
	<li><?= a($editLinkPrefix . $item[$idColumn], $item[$keyColumn]) ?></li>
<? endforeach; ?>
</ul>
	
<? break; ?>
<? endswitch; ?>

<? else: ?>
<p class="error"><?= $_JAG['strings']['admin']['moduleEmptyForThisLanguage'] ?></p>
<? endif; ?>
