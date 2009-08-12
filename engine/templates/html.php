<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?= $_JAM->language ?>" >
<head>
	<title><?= $_JAM->title ?></title>
	<? if ($css): ?><? foreach ($css as $stylesheet): ?>
	<link rel="stylesheet" href="<?= $stylesheet['file'] ?>" type="text/css" media="<?= $stylesheet['media'] ?>" />
	<? endforeach; ?><? endif; ?>
	<? if ($js): ?><? foreach ($js as $script): ?>
	<script type="text/javascript" charset="utf-8" src="<?= $script ?>"></script>
	<? endforeach; ?><? endif; ?>
	<? if ($meta): ?><? foreach ($meta as $attributes): ?>
	<meta name="<?= $attributes['name'] ?>" content="<?= $attributes['content'] ?>" />
	<? endforeach; ?><? endif; ?>
</head>
<body<?= $bodyClass ? ' class="'. $bodyClass .'"' : '' ?>>

	<?= $body ?>

</body>
</html>
