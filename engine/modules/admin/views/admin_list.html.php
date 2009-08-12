<? if ($items): ?>

<? if ($this->config['adminExportFields']): ?>
<a class="action" href="<?= ROOT . $_JAM->request ?>?a=export"><?= $_JAM->strings['admin']['exportButton'] ?></a>
<? endif;?>

<? switch ($sortFieldType):
	case 'file': ?>

<ul class="images">
<? foreach ($items as $item): ?>
	<? $file = $item[$sortField]; ?>
	<? switch ($file->item['type']) :
		case 'image/jpeg':
		case 'image/png':
		case 'image/gif': ?>
			<li><?= a($editLinkPrefix . $item['id'], i($file->item['path'] . '?context=adminList', $file->item['filename'])) ?></li>
			<? break; ?>
		<? default: ?>
			<li><?= a($editLinkPrefix . $item['id'], i('assets/images/admin_bigfile.png', $file->item['filename'])) ?></li>
			<? break; ?>
	<? endswitch; ?>
<? endforeach; ?>
</ul>

<? break; ?>
<? default: ?>

<? if ($this->config['allowSort'] == 'désactivé temporairement'): ?>
<div class="hiddenAction">
<span><?= $_JAM->strings['admin']['reorderItems'] ?></span>
</div>
<? endif;?>

<table id="<?= $this->name ?>Table" class="items<?= ($this->config['allowSort']) ? ' sortable' : '' ?>">
	<thead>
		<tr>
		<? foreach ($headerStrings as $field => $headerString): ?>
			<th>
				<? $sortLink = $_JAM->request .'?s='. $field ?>
				<? if ($field == $sortField): ?>
					<?= a($sortLink . ($field != 'sortIndex' ? '&amp;r='. (int)!$reverseSort : ''), $headerString, array('class' => $reverseSort ? 'sort up' : 'sort down')) ?>
				<? else: ?>
					<?= a($sortLink, $headerString) ?>
				<? endif; ?>
			</th>
		<? endforeach; ?>
		</tr>
	</thead>
	<tbody>
	<? foreach ($items as $item): ?>
		<tr id="row<?= $item['master'] ? $item['master'] : $item['id'] ?>">
			<? foreach ($tableFields as $key => $field): ?>
			<td>
				<? $class = get_class($item[$field]) ?>
				<? if ($class == 'Date' || $class == 'date'):
					$label = $item[$field]->SmartDateAndTime();
				elseif ($this->schema[$field]['type'] == 'bool'):
					$label = ucfirst($item[$field] ? $_JAM->strings['words']['affirmative'] : $_JAM->strings['words']['negative']);
				elseif ($relatedArray = $relatedArrays[$field]):
					$label = $relatedArray[$item[$field]];
				else:
					$label = $item[$field];
				endif; ?>
				<?= $field == $linkField ? a($editLinkPrefix . ($item['master'] ? $item['master'] : $item['id']), $label) : $label ?>
			</td>
			<? endforeach; ?>
			<? if ($this->CanDelete()): ?>
			<td class="delete"><?= a('admin/'. $this->name .'?a=delete&amp;id='. ($item['master'] ? $item['master'] : $item['id']), $_JAM->strings['admin']['delete']) ?></td>
			<? endif; ?>
		</tr>
	<? endforeach; ?>
	</tbody>
</table>
	
<? break; ?>
<? endswitch; ?>

<? else: ?>
<p class="error"><?= $_JAM->strings['admin']['moduleEmptyForThisLanguage'] ?></p>
<? endif; ?>
