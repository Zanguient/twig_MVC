<?php

include_once('MyView.php');

$t = new MyView();

$t->friends = array(
    'Rachel', 'Monica', 'Phoebe', 'Chandler', 'Joey', 'Ross'
);

$t->render('index.phtml');
$t->render('index.xml');


?>