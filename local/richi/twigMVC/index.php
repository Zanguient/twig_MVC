<!DOCTYPE html> 
<html> 
<head> 
	<title>Richi's MVC with Twig</title> 
	<meta name="viewport" content="width=device-width, initial-scale=1"> 
	<link rel="stylesheet" href="http://code.jquery.com/mobile/1.2.0/jquery.mobile-1.2.0.min.css" />
</head> 
<body>
<?php
    require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
    require_once(dirname(__FILE__) . '/controllers/CourseController.php');
    require_once(dirname(__FILE__). '/models/CourseModel.php');
    require_once(dirname(__FILE__). '/views/CourseView.php');
	
    $task = optional_param('task', 'loadCourses', 'string');
    require_login();
    
    //Load Twig_Environment
    //require_once '/home/riccardo/Projects/twig/vendor/autoload.php';
	//Twig_Autoloader::register();
	//$loader = new Twig_Loader_Filesystem('./templates');
	//$twig = new Twig_Environment($loader, array('cache' => './tmp/cache'));
    require "{$CFG->dirroot}/bootstrap_twig.php";
    
    //Load Model 
    $courseModel = new CourseModel($USER->id, $DB);
    
    //load Controller
    $courseController = new CourseController($courseModel);
    
    $courseController->load('local/mobile/richicourses.twig');
    
    
    
    
    
    
    /*require_once '/home/riccardo/Projects/twig/vendor/autoload.php';
	//Twig_Autoloader::register();
	$loader = new Twig_Loader_Filesystem('./templates');
	$twig = new Twig_Environment($loader, array('cache' => './tmp/cache'));

	//$template = $twig->loadTemplate('hello.phtml');

	//$template = $twig->loadTemplate('hello.xml');

    /*$twigXML = XML::Twig->new();    # create the twig
    $twigXML->parsefile( 'doc.xml'); # build it
    my_process( $twig);           # use twig methods to process it 
    $twigXML->print;*/       

    /*$params = array(
    'name' => 'Krzysztof',
    'friends' => array(
        				array(
            					'firstname' => 'John',
            					'lastname' => 'Smith'
        					 ),
        				array(
            				'firstname' => 'Britney',
            			 	'lastname' => 'Spears'
        				),
        				array(
            				'firstname' => 'Brad',
            				'lastname' => 'Pitt'
        				)
    				    )
    );
    $template->display($params);*/
?>

</body>
</html>
