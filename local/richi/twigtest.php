<!DOCTYPE html> 
<html> 
<head> 
	<title>Twig Test</title> 
	<meta name="viewport" content="width=device-width, initial-scale=1"> 
	<link rel="stylesheet" href="http://code.jquery.com/mobile/1.2.0/jquery.mobile-1.2.0.min.css" />
	<script src="http://code.jquery.com/jquery-1.8.2.min.js"></script>
	<script src="http://code.jquery.com/mobile/1.2.0/jquery.mobile-1.2.0.min.js"></script>
	<script src="js/my.js"></script>
</head> 
<body>
<?php
	require_once '/home/riccardo/Projects/twig/vendor/autoload.php';
	$loader = new Twig_Loader_String();
	$twig = new Twig_Environment($loader);
	$twig = new Twig_Environment($loader, array('cache' => '/path/to/compilation_cache',));
	echo $twig->render('Hello {{ name }}!', array('name' => 'Magic Johnson'));
?>

</body>
</html>