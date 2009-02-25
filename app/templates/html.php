<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>Bidon</title>
	<link rel="stylesheet" href="<?= ROOT ?>assets/css/reset.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="<?= ROOT ?>assets/css/screen.css" type="text/css" media="screen" />
</head>
<body>

<div id="container">
	<h1>Site web bidon.</h1>
	<ul id="menu">
		<li><?= a('accueil', 'Accueil') ?></li>
		<li><?= a('actualite', 'Actualités') ?></li>
	</ul>
	<div id="body">
		<?= $body ?>
	</div>
</div>

</body>
</html>
