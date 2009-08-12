<h2>Actualité</h2>
<dl>
	<? if($items): ?>
		<? foreach($items as $item): ?>
		<dt><?= a($item['path'], $item['title']) ?> <span class="metadata"><?= $item['modified']->SmartDate() ?> dans <?= $item['category_title'] ?></span></dt>
		<dd><?= $item['teaser'] ?></dd>
		<? endforeach; ?>
	<? else: ?>
		<p>Aucune nouvelle pour l'instant.</p>
	<? endif; ?>
</dl>