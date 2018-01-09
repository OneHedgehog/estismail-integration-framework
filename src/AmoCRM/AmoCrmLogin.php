<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 11/9/17
 * Time: 12:16 PM
 */

namespace Integration\AmoCRM;

use Integration\Common\Postgres;
use Integration\Common\Event;
use Integration\Common\EstisApi;

use Exception;


class AmoCrmLogin extends Event {


	public function __construct() {
		parent::__construct();
	}

	public function exec(){

		$post_data = $this->checkInputPostParams($_POST);

		$estis_user = $this->checkEstisConnection($post_data['api_key']);

		if(!$estis_user){
			$this->ajaxMes('Connection failed. Please, contact to support');
		}

		if($post_data['active'] === 'true'){

			$this->setAmoUser($post_data['api_key'], $post_data['user_id'], $post_data['user']);
			die(json_encode(array('estismail' => array(
				'id'=>$post_data['user_id'],
				'hash'=>sha1($post_data['user_id'] . '39NeSeBANo6Lqfe7lOvmL9c24NTdi0lvwOfZaHbt'))),
				ENT_QUOTES));
		}

		if($post_data['active'] === 'false'){
			$this->deleteDb($post_data['user_id']);
		}

		die(json_encode(array('mes'=>'Succefully connected. Please activate your plugin'), ENT_QUOTES));

	}

	/**
	 * @param $post_data
	 *
	 * @return mixed
	 */
	private function checkInputPostParams($post_data){
		if(empty($post_data['user'])){
			$this->ajaxMes('Empty amouser');
		}

		if(empty($post_data['user_id'])){
			$this->ajaxMes('Empty amouser_id');
		}
		$post_data['user_id']*=1;

		if(empty($post_data['active'])){
			$this->ajaxMes('Empty active params');
		}

		return $post_data;
	}


	/**
	 * @param $api_key
	 *
	 * @return string
	 */
	private function checkEstisConnection($api_key){
		if(!preg_match("/^[a-z\d]{40}$/i", $api_key)){
			$this->ajaxMes('Invalid api key');
		}
		$estis = EstisApi::getInstance($api_key);
		$res = $estis->estisApiQuery('/mailer/users');

		$err = $estis->getErr();
		if($err !== ''){
			$this->ajaxMes($err);
		}

		return $res;

	}

	/**
	 * @param $api_key
	 * @param $user_id
	 * @param $user
	 */
	private function setAmoUser($api_key, $user_id, $user){
		$pgsql = Postgres::getInstance();
		try{
			$pgsql->query("INSERT INTO amocrm_table ( api_key, user_id, email) VALUES ($1 , $2 , $3)",
				array($api_key, $user_id, $user));
		} catch ( Exception $ex){
			$this->ajaxEx($ex);
		}
	}

	/**
	 * @param $user_id
	 */
	private function deleteDb($user_id){
		$pgsql = Postgres::getInstance();
		try{
			$pgsql->query('DELETE FROM amocrm_table WHERE user_id = $1', array($user_id));
		} catch ( Exception $ex){
			$this->ajaxEx($ex);
		}
	}




}