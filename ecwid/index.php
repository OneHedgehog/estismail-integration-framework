<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 9/29/17
 * Time: 3:27 PM
 */

use Integration\Ecwid\EcwidApp;
use Integration\Ecwid\Webhook;
use Integration\Ecwid\ApiConnect;
use Integration\Ecwid\SaveEstisSettings;
use Integration\Ecwid\MainAppPage;

switch($_GET['action']){
	case('estismailApp'):{
		$instance = new EcwidApp();
	} break;
	case('api-connect'):{
		$instance = new ApiConnect();
	} break;
	case('webhook'):{
		$instance = new Webhook();
		$instance->setPostRawData($HTTP_RAW_POST_DATA);
	} break;
	case('save-estis-settings'):{
		$instance = new SaveEstisSettings();
	} break;
	case('main-app-page'):{
		$instance = new MainAppPage();
	} break;
}

$instance->exec();


