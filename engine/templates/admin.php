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
	<a id="logout" href="?a=logout">Logout</a>

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
