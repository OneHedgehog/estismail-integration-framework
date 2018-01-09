<?php

use Integration\MoySklad\MainPage;
use Integration\MoySklad\MoySkladApiConnect;
use Integration\MoySklad\SaveEstisApiKey;
use Integration\MoySklad\SaveEstisSettings;
use Integration\MoySklad\SubscribeAll;
use Integration\MoySklad\Webhook;
use Integration\MoySklad\SubscribeList;

switch ($_GET['action']){
    case ('main-page'):{
        $instance = new MainPage();
    } break;
    case ('moysklad-login'):{
        $instance = new MoySkladApiConnect();
    } break;
    case ('save-estis-api-key'):{
        $instance = new SaveEstisApiKey();
    } break;
    case ('save-estis-settings'):{
        $instance = new SaveEstisSettings();
    } break;
    case ('subscribe-all'):{
        $instance = new SubscribeAll();
    } break;
    case('webhook'):{
    	//file_put_contents('file.txt', $HTTP_RAW_POST_DATA);
	    //$HTTP_RAW_POST_DATA = file_get_contents('file.txt');
	    //print_r($HTTP_RAW_POST_DATA);
	    $instance = new Webhook();
    	$instance->setRawData($HTTP_RAW_POST_DATA);
    } break;
    case ('subscribe-list'):{
        $instance = new SubscribeList();
    } break;
}
$instance->exec();