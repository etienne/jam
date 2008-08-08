<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=$_JAG['language']?>" >
<head>
	<title><?= $_JAG['project']['projectName'] .' – Admin' ?></title>
	<link rel="stylesheet" href="<?= ROOT ?>assets/css/reset.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="<?= ROOT ?>assets/css/admin.css" type="text/css" media="screen" />
	<script type="text/javascript" src="<?= ROOT ?>assets/js/jquery.js"></script>
	<script type="text/javascript" src="<?= ROOT ?>assets/js/admin.js"></script>
</head>
<body>

	<h1><?= $_JAG['project']['projectName'] ?></h1>
	<ul id="toplinks">
		<? foreach ($_JAG['project']['languages'] as $language): ?>
		<? if ($language != $_JAG['language']): ?>
		<li><a href="?language=<?= $language ?>"><?= $_JAG['strings']['languages'][$language] ?></a></li>
		<? endif; ?>
		<? endforeach; ?>
		<li><a href="<?= ROOT ?>"><?= $_JAG['strings']['admin']['returnToSite'] ?></a></li>
		<li><a id="logout" href="?a=logout"><?= $_JAG['strings']['admin']['logout'] ?></a></li>
	</ul>

	<? if($_JAG['user']->IsWebmaster()): ?>
	<ul id="menu">
		<? foreach($_JAG['installedModules'] as $module): ?>
			<? if ($menuStrings = Module::ParseConfigFile($module, 'config/admin.ini')): ?>
				<li><?= a('admin/'. $module, $menuStrings[$_JAG['language']]) ?></li>
			<? endif; ?>
		<? endforeach; ?>
	</ul>
	<? endif; ?>

	<div id="body">
		<? if($messageString = $_JAG['strings']['adminMessages'][$_GET['m']]): ?>
			<p class="message"><?= $messageString ?></p>
		<? endif; ?>
		<?= $_JAG['body'] ?>
	</div>

</body>
</html>
