<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

//require autoload ( care with namespaces )
require_once __DIR__ . '/vendor/autoload.php';

define('ESTIS_HOST_NAME', 'https://proger.estiscloud.pro');

$systems = array('insales', 'ecwid', 'moysklad', 'amoCRM');
$valid = false;

if (in_array($_GET['system'], $systems)) {
    $valid = true;
} else {
    die('System undefined');
}



require_once ($_GET['system'] . '/index.php');







