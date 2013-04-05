<?php

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

// Doctrine and Twig
require "{$CFG->dirroot}/bootstrap_doctrine_dbal.php";
require "{$CFG->dirroot}/bootstrap_twig.php";

// map the http request to a view
$view = '\mobile\\' . optional_param('view', 'enrolled_courses', PARAM_ALPHAEXT);
require_once(dirname(__FILE__) . '/views.php');
if (!function_exists($view)) {
    $view = '\mobile\enrolled_courses';
}
$view();
