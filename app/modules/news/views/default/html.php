<? if($items): ?>
<ul class="news">
	<? foreach($items as $item): ?>
	<li>
		<h2><?= $item['title'] ?></h2>
		<p class="metadata"><?= $item['modified']->SmartDate() ?> dans <?= $item['category_title'] ?></p>
		<div class="newsText">
			<?= $item['body'] ?>
		</div>
	</li>
	<? endforeach; ?>
</ul>
<? else: ?>
<? endif; ?>