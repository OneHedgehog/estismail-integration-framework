# Estismail integration project

Integrate estismail(https://my.estismail.com/login) account with:

* [amoCRM](https://www.amocrm.ru/) - The web CMS used by managers etc.
* [Ecwid](https://www.ecwid.com/) - Ecwid web store
* [InSales](https://www.insales.com.ua/) - InSales web store
* [MoySklad](https://www.moysklad.ru/) - InSales web stock

### amoCRM

Create an user interface for customer subscription. Subscribe in transactions section

### Ecwid

Subscribe user after checkout. Add a checkbox for subscription confirm.


### InSales

Subscribe user after checkout. Add a checkbox for subscription confirm.

### MoySklad

Subscribe by adding a user to owner account


### How it works...

## Entry point ( step 1)

first look at '.htaccess' in root folder:

```
RewriteRule ^([^\/]+)/([^\/]+)\/?(?:\?(.*))?$ integration/index.php?system=$1&action=$2&$3 [QSA,N,L]
```

That code translate url like 

```
http://proger.estiscloud.pro/integration/insales/login/?get_param=pertty_girl
```

to


```
integration/index.php?system=insales&action=login&get_param=pertty_girl
```


Сarefully check $_GET params from that url. We get there:


```
array(
	system=>'insales',
	action=>'login',
	get_param=>'pretty_girl'
)


```

## index.php as entry point ( step 2 )


look at index.php in root folder:

```
<?php
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

```


we check there, if the $_GET['system'] from prev section valid and if it is we require index.php
for our system


## index.php for $_GET['action'] ( step 3 )

Look at '/insales/index.php'

```
<?php
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

```
 

Here he we call a object instance for every action. Using singleton. Actions classes are available here thanks to composer setting. Check it out, if you want.

## Action class ( step 4 )

check '/src/InSales/Login.php'


```
<?php
namespace Integration\InSales;
use Integration\Common\Event;
use Integration\Common\Postgres;
use Integration\Common\EstisApi;
use Exception;

class Login extends Event {
	protected $view = 'login';
	protected $system_dir = __DIR__;
	public function __construct() {
		parent::__construct();
	}
	public function exec() {
		$_GET = $this->checkParams($_GET);
		$this->data['alert_data']= array(
			'0'=>array(
				'class'=>'',
				'mes'=>'',
			),
			'1'=>array(
				'class'=>'success',
				'mes'=>'Connected :)',
			),
			'2'=>array(
				'class'=>'danger',
				'mes'=>'Connection failed :(',
			)
		);
		//get Data from db and move it to view ( we need all data in view, cause we use hidden input to data transfer )
		$this->data['db'] = $this->selectAllInsalesDataFromDb();
		//compare $_GET input with db data, we get from Install
		$this->compareInputInsalesDataWithDb($this->data['db']);
		//we check one more time. Cause if we go further, we would make one more db query ( We also have data, so don't should do UPDATE query )
		if(empty($this->data['db']['user_id'])){
			//can check only user_id or user_emails, cause another GET params we have in install
			$id = $this->data['db']['id'];
			$this->insalesGetParamsToDb($id);
		}
		$this->data['db']['user_email'] = $_GET['user_email'];
		$api_key = $this->data['db']['api_key'];
		if(!preg_match("/^[a-z\d]{40}$/i", $api_key)){
			$this->data['alert_status'] =  0;
			//connection failed status
			if(!empty($api_key)){
				$this->data['alert_status'] = 2;
			}
			//to collect api_key connection & list data in one method
			$this->render();
		}
		$this->data['estis'] = $this->getDataFromEstis($api_key);
		$this->data['alert_status'] = 2;
		if(!empty($this->data['estis']['user'])){
			$this->data['alert_status'] = 1;
		};
		$this->render();
	}

	/**
	 * Checking login GET params from insales. Die if !valid
	 * @param $get_params $_GET array()
	 * @return $get params $_GET array()
	 */
	private function checkParams($get_params){
		$get_params['insales_id']*=1;
		if(empty($get_params['insales_id'])){
			$this->logMes('Empty or invalid $_GET[ \'insales_id\' ] =  (' .  $get_params['insales_id']. ')');
		}
		if(empty($get_params['shop'])){
			$this->logMes('Empty $_GET[ \'shop\' ] (' .  $get_params['shop']. ')');
		}
		$get_params['shop'] = htmlentities( $get_params['shop'], ENT_QUOTES);
		$get_params['user_id'] *= 1;
		if(empty($get_params['user_id'])){
			$this->logMes('Empty or invalid $_GET[ \'user_id\' ] = (' .  $get_params['user_id']. ')');
		}
		// email
		if(empty($get_params['user_email'])){
			$this->logMes('Empty  $_GET[ \'user_email\' ] = (' .  $get_params['user_email']. ')');
		}
		if(!filter_var($get_params['user_email'], FILTER_VALIDATE_EMAIL)){
			$this->logMes('Invalid value of  $_GET[ \'user_email\' ] = (' .  $get_params['user_email']. ')');
		}
		return $get_params;
	}

	/**
	 * select all data from in_sales db by autoinc id, which we setted in install
	 * @return array ( array of values from db )
	 */
	private function selectAllInsalesDataFromDb(){
		$pgsql = Postgres::getInstance();
		//0 for first row
		try{
			//[0] cause we get from db array of rows, and now we need only first row
			$data = $pgsql->query("SELECT * FROM in_sales WHERE insales_id = $1 LIMIT 1", array($_GET['insales_id']));
			if($data === null){
				$this->logMes('SELECT * FROM in_sales WHERE insales_id =' . $_GET["insales_id"] . ' LIMIT 1 was return emty array()' );
			}
		} catch ( Exception $ex ){
			$this->logEx($ex);
		}
		//process specific data from db
		$data = $data[0];
        $data['last_subscribe_email'] = json_decode($data['last_subscribe_email'], true);
		$data['double_opt_in'] = isset($data['double_opt_in']) ? true : false;
		$data['err_proc'] = json_decode($data['subscribe_all'], true);
		return $data;
	}

	/**
	 * @param $db
	 * compare $_GET data with Db. Die if fail
	 */
	private function compareInputInsalesDataWithDb($db){
		// shop
		if($db['shop'] != $_GET['shop']){
			$this->logMes('$this->data[\'db\'][\'shop\'] is not equal with  $_GET[\'shop\'] ('.  $this->data['db']['shop'] . '!='. $_GET['shop'] . ' ) in shop with insales_id = ' . $this->data['db']['insales_id']);
		}
	}

	/**
	 * update $_GET['user_id'] && $_GET['user_email] by id. Die, if we have DB error
	 * @param $id
	 */
	private function insalesGetParamsToDb($id){
		$pgsql = Postgres::getInstance();
		try{
			$pgsql->query("UPDATE in_sales SET user_id = $1, user_email = $3  WHERE id = $2",array($_GET['user_id'], $id, $_GET['user_email']));
		} catch ( Exception $ex ){
			$this->logEx($ex);
		}
	}

	/**
	 * just loop for api queries, cause we should call Estis Api each time this url, when this url is open
	 * @param $api_key
	 *
	 * @return array
	 */
	private function getDataFromEstis($api_key){
		//key must be the same, as estis method keys
		$method = [
			'user'=>'/mailer/users',
			'lists'=>'/mailer/lists'
		];
		//response from estis Api
		$data = array();
		$estis = EstisApi::getInstance($api_key);
		foreach ( $method as $key=>$value ){
			$res = $estis->estisApiQuery($value);
			if($res === false){
				return $data;
			}
			$data[$key] = $res[$key];
		}
		return $data;
	}
}
```


As you see you remeber, entry point here is 'exec' method. So, if you want to check logic, start from it. Also, every $_GET['action'] method extends to Event class.


## Event class ( step 5 )

navigate to '/src/Common/Event.php'


```
<?php

namespace Integration\Common;
class Event  {
	protected $view = '';
	protected $system_dir = '';
	protected $data = array();
	public function __construct() {
		$this->path = ('/var/log/integration/' . $_GET['system'] . '/Log.log');
	    
	}
	public function exec() {
	}
	protected function render() {
		if (!$this->view) {
			return;
		}
		//This is used in render view
		$view_data = $this->data;
		//we are now in /var/www/integration/src/Common directory
		$view_path = ( $this->system_dir . '/Views/'. $this->view .'.view.php');
		require_once($view_path);
		die();
	}
	protected function logMes($mes){
		$log = array(
			'script_name' => $_SERVER['SCRIPT_URI'],
			'mes' => $mes,
			'time' => date('d/m/Y H:m:s'),
			'action' => $_GET['action'],
			'system' => $_GET['system']
		);
		$data = file_get_contents($this->path);
		$data = $data . "\n" . json_encode($log);
		file_put_contents($this->path,$data);
		die($mes);
	}
	protected function logEx($ex){
		$log = array(
			'script_name' => $_SERVER['SCRIPT_URI'],
			'mes' => $ex->getMessage(),
			'time' => date('d/m/Y H:m:s'),
			'action' => $_GET['action'],
			'system' => $_GET['system'],
			'file'=>$ex->getFile(),
		);
		$data = file_get_contents($this->path);
		$data = $data . "\n" . json_encode($log);
		file_put_contents($this->path,$data);
		die($ex->getMessage());
	}
	protected function ajaxMes($mes){
		$log = array(
			'script_name' => $_SERVER['SCRIPT_FILENAME'],
			'mes' => $mes,
			'time' => date('d/m/Y H:m:s'),
			'action' => $_GET['action'],
			'system' => $_GET['system']
		);
		$data = file_get_contents($this->path);
		$data = $data . "\n" . json_encode($log);
		file_put_contents($this->path,$data);
		$ajax_mes = [
			'error'=> $mes
		];
		die(json_encode($ajax_mes, ENT_QUOTES));
	}
	protected function ajaxEx($ex){
		$log = array(
			'script_name' => $_SERVER['SCRIPT_FILENAME'],
			'mes' => $ex->getMessage(),
			'time' => date('d/m/Y H:m:s'),
			'action' => $_GET['action'],
			'system' => $_GET['system'],
			'file'=>$ex->getFile(),
		);
		$data = file_get_contents($this->path);
		$data = $data . "\n" . json_encode($log);
		file_put_contents($this->path,$data);
		$ajax_mes = [
			'error'=> 'db error. Please contact to support'
		];
		die(json_encode($ajax_mes, ENT_QUOTES));
	}

	//@params for hash in url, like in ecwid ( something, after '#' called hash )
	protected function redirectBack($params = '') {
		header('Location: ' . $_SERVER['HTTP_REFERER'] . $params);
		die();
    }
}

```


* logEx and ajaxEx methods used, when we have an arr, after wich app crashed. It also write logs.
* logMes and ajaxMes is for a temp erros, which may gone after user refresh page
* render func, to render template

Check render function, it provide a path for view file and a varible for view data


## template ( step 6 )

go to '/src/InSales/Views/login.view.php'

```
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>InSales estismail :)</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css"
          integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
    <link rel="stylesheet" href="/integration/insales/css/main.css">
</head>
<body>
<video loop muted autoplay poster="/integration/insales/img/I_Just_Wanted.jpg"
       class="fullscreen-bg__video">
    <source src="/integration/insales/img/I_Just_Wanted.mp4" type="video/mp4">
    <source src="/integration/insales/img/I_Just_Wanted.webm" type="video/webm">
</video>
<div class="container">
    <div class="row">
        <div class="col-lg-6 center">
            <div class="InSales">
                <p>
                    <b>Insales Account:
                    </b>
                    <span>
                    <?php echo( $view_data['db']['shop'] ) ?>
                    </span>
                </p>
                <p>
                    <b>Insales Email:
                    </b>
                    <span>
                    <?php echo( $view_data['db']['user_email'] ) ?>
                    </span>
                </p>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6 center">
            <div class="estis_form">
                <div class="head">
                    <h3>Estismail InSales app</h3>
                </div>
                <div class="alert alert-<?php echo( $view_data['alert_data'][ $view_data['alert_status'] ]['class'] ) ?> estis-control"
                     role="alert">
                    <strong><?php echo( $view_data['alert_data'][ $view_data['alert_status'] ]['mes'] ) ?></strong>
                </div>
                <form action="/integration/insales/save-api-key/" method="POST">
                    <div class="form-group">
                        <label for="api_key">Api key</label>
                        <input type="text" value="<?php echo( $view_data['db']['api_key'] ) ?>" name="api_key"
                               class="form-control" id="api_key" required>
                        <input type="hidden" value="<?php echo( $view_data['db']['id'] ) ?>" name="id"/>
                        <input type="hidden" value="<?php echo( sha1( 'RYqxUsnCkpQpQMyfv23vopsBBE72aRV6LQ0quLDI' . $view_data['db']['id'] ) ) ?>"
                               name="id_hash"/>
                    </div>
                    <button class="btn btn-outline-success">Submit</button>
                </form>
            </div>
        </div>
    </div>

	<?php if ( $view_data['alert_status'] === 1 ): ?>
        <!-- Modal -->
        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
             aria-hidden="true">
            <form method="POST"
                  action="/integration/insales/subscribe-all-clients/">
                <input type="hidden" value="<?php echo( $view_data['db']['id'] ) ?>" name="id">
                <input type="hidden" value="<?php echo( sha1('hp9Ur8Rvw0tTTC7WbklFkUKYqKzH7bxL072V8Wru' . $view_data['db']['id'] )) ?>" name="hash_id">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Warning</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            Adding all clients is a long process. Are u sure, that you want to do it?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-danger" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-outline-success allEmails">Get All</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="row">
            <div class="col-lg-6 center">
                <div class="estis_form_lists">

                    <div class="head">
                        <h3>Estis Settings</h3>
                    </div>

					<?php if ( empty( $view_data['db']['list_id'] ) ): ?>
                        <div class="alert alert-warning" role="alert">
                            <b>Please, update your settings !</b>
                        </div>
					<?php endif; ?>
                    <form action="/integration/insales/save-estis-settings/"
                          method="post">
                        <input type="hidden" value="<?php echo( $view_data['db']['id'] ) ?>" name="id">
                        <input type="hidden" value="<?php echo( sha1( 'x9KRMg2rmitUTkviVQHxoOuD0PmTOmKatNWPJ7gm' . $view_data['db']['id'] ) ) ?>"
                               name="id_hash"/>
                        <div class="form-group">
                            <label for="list">Lists</label>
							<?php if ( isset( $view_data['estis']['lists'] ) ): ?>
                                <select name="list" id="lists" class="form-control" required>
									<?php if ( empty( $view_data['db']['list_id'] ) ): ?>
                                        <option disabled="" default="" selected="true" value="0">Choose your list:
                                        </option>
									<?php endif; ?>
									<?php foreach ( $view_data['estis']['lists'] as $list ): ?>
                                        <option value="<?php echo( $list['id'] ) ?>" <?php if ( $view_data['db']['list_id'] == $list['id'] ) {
											echo( 'selected' );
										} ?>><?php echo( $list['title'] ) ?></option>
									<?php endforeach; ?>
                                </select>
							<?php else: ?>
                                <select name="list" id="list" class="form-control">
                                    <option value=""></option>
                                </select>
							<?php endif; ?>
                        </div>
                        <div class="form-group btn-div">
                            <div class="form-check">
                                <label class="form-check-label">
                                    <input type="checkbox" class="form-check-input"
                                           name="double_opt_in"
                                           value="1"
										<?php echo( $view_data['db']['double_opt_in'] == 1 ? 'checked' : '' ) ?>>
                                    Double opt-in
                                </label>
                            </div>
                            <button class="btn btn-outline-success">Save settings</button>
                        </div>
	                    <?php if ( ! empty( $view_data['db']['last_subscribe_email'] ) ): ?>
                            <p>
                                <b>Last order subscribe:</b>
                                <span>
                                <?php if ( ( $view_data['db']['last_subscribe_email']['valid'] ) === false ): ?>
                                    Subscription error in order id: <?php echo( $view_data['db']['last_subscribe_email']['order_id'] ) ?>
                                <?php elseif ( ( $view_data['db']['last_subscribe_email']['valid'] ) === true): ?>
	                                <?php echo( $view_data['db']['last_subscribe_email']['email'] ) ?>
                                <?php endif; ?>
                            </span>
                            </p>

	                    <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 center">
                <div class="subscribe_all_form">
                    <div class="head">
                        <h3>Subscribe All</h3>
                    </div>
                    <button type="button" class="btn btn-outline-success" data-toggle="modal"
                            data-target="#exampleModal">
                        Add all clients
                    </button>
	                <?php if ( ! empty( $view_data['db']['err_proc']['success'] ) ): ?>
                        <ul class="list-group estis_subscribe_all_listing">
                            <li class="col-lg-6 list-group-item">
                                Succefully subscribed:
                                <b><?php echo( $view_data['db']['err_proc']['success'] ) ?></b>
                            </li>
                            <li class="col-lg-6 list-group-item">
                                Subscription errors:
                                <b><?php echo( $view_data['db']['err_proc']['err'] ) ?></b>
                            </li>
                        </ul>

	                <?php endif; ?>
                </div>
            </div>
        </div>

	<?php endif; ?>

</div>


<!--modal for api_key value control-->
<div class="modal" id="api-key-value-control">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Api key validation</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Api key should be less then 40 symbols</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-success" data-dismiss="modal">Ok</button>
            </div>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"
        integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN"
        crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"
        integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4"
        crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js"
        integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1"
        crossorigin="anonymous"></script>
<script src="/integration/insales/js/login.js"></script>
</body>
</html>
```


and check the template

