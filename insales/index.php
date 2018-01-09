<?
use Integration\InSales\Login;
use Integration\InSales\Install;
use Integration\InSales\Uninstall;
use Integration\InSales\Subscribe;
use Integration\InSales\SubscribeAll;
use Integration\InSales\SaveEstisSettings;
use Integration\InSales\SaveApiKey;

switch ($_GET['action']){
	case ('install'):{
		$instance = new Install();
	} break;
	case('login'):{
		$instance = new Login();
	} break;
	case('uninstall'):{
		$instance = new Uninstall();
	} break;
	case('subscribed'):{
		$instance = new Subscribe();
		$instance->setRawData($HTTP_RAW_POST_DATA);
	} break;
	case('subscribe-all-clients'):{
		$instance = new SubscribeAll();
	} break;
	case('save-estis-settings'):{
		$instance = new SaveEstisSettings();
	} break;
	case('save-api-key'):{
		$instance = new SaveApiKey();
	} break;
}

$instance->exec();