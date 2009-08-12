<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=$_JAM->language?>" >
<head>
	<title><?= $_JAM->projectConfig['projectName'] .' – Admin' ?></title>
	<link rel="stylesheet" href="<?= ROOT ?>assets/css/reset.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="<?= ROOT ?>assets/css/admin.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="<?= ROOT ?>assets/css/adminPrint.css" type="text/css" media="print" />
	<script type="text/javascript" src="<?= ROOT ?>assets/js/jquery.js"></script>
	<script type="text/javascript" src="<?= ROOT ?>assets/js/jquery.tablednd_0_5.js"></script>
	<script type="text/javascript" src="<?= ROOT ?>assets/js/admin.js"></script>
	<script type="text/javascript" src="<?= ROOT ?>assets/js/fckeditor/fckeditor.js"></script>
</head>
<body>

	<div id="header">
		<h1><?= $_JAM->projectConfig['projectName'] ?></h1>
		<ul id="toplinks">
			<? foreach ($_JAM->projectConfig['languages'] as $language): ?>
			<? if ($language != $_JAM->language): ?>
			<li><a href="?language=<?= $language ?>"><?= $_JAM->strings['languages'][$language] ?></a></li>
			<? endif; ?>
			<? endforeach; ?>
			<li><a href="<?= ROOT ?>"><?= $_JAM->strings['admin']['returnToSite'] ?></a></li>
			<li><a id="logout" href="?a=logout"><?= $_JAM->strings['admin']['logout'] ?></a></li>
		</ul>
	</div>
	
	<? if($_JAM->user->IsWebmaster()): ?>
	<ul id="menu">
		<? foreach($_JAM->installedModules as $module): ?>
			<? if ($menuString = Module::GetAdminMenuString($module)): ?>
				<? $link = 'admin/'. $module ?>
				<li<?= $link == $_JAM->request ? ' class="current"' : '' ?>><?= a('admin/'. $module, $menuString) ?></li>
			<? endif; ?>
		<? endforeach; ?>
	</ul>
	<? endif; ?>
	
	<div id="body">
		<? if($messageString = $_JAM->strings['adminMessages'][$_GET['m']]): ?>
			<p class="message"><?= $messageString ?></p>
		<? endif; ?>
		<?= $body ?>
	</div>

</body>
</html>
