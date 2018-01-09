<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 11/7/17
 * Time: 5:04 PM
 */

use Integration\AmoCRM\TESTCLASS;
use Integration\AmoCRM\AmoCrmInstall;
use Integration\AmoCRM\AmoCrmLogin;
use Integration\AmoCRM\AmoCrmSaveApiKey;
use Integration\AmoCRM\AmoCrmGetLists;
use Integration\AmoCRM\AmoCrmSubscribe;

switch($_GET['action']) {
	case('login'):{
		$instance = new AmoCrmLogin();
	} break;
    case('get-lists'):{
        $instance = new AmoCrmGetLists();
        $instance->setInputPostData($_POST);
    } break;
    case('subscribe'):{
		$instance = new AmoCrmSubscribe();
		$instance->setInputPostData($_POST);
    } break;
}

$instance->exec();
