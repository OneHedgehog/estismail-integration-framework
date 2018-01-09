<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 9/25/17
 * Time: 12:42 PM
 */

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