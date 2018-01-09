<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 11/14/17
 * Time: 12:59 PM
 */

namespace Integration\AmoCRM;

use Integration\Common\Event;
use Integration\Common\Validator;
use Integration\Common\Postgres;
use Integration\Common\EstisApi;

use Exception;


class AmoCrmSubscribe extends Event{

	private $estis_error = '';
	private $post_data = [];

	public function __construct() {
		parent::__construct();
	}

	public function exec() {
		parent::exec();
		$this->checkInputParams();
		$db_data = $this->selectDbData($this->post_data['id']);

		$estimail_subscribe_data = array(
			'email'=> $this->post_data['email'],
			'list_id'=> $this->post_data['list_id']
		);

		$this->subscribeToEstis($db_data['api_key'], $estimail_subscribe_data);

		if($this->estis_error){
			$this->ajaxMes($this->estis_error);
		}

		die(json_encode(array('success'=>$estimail_subscribe_data['email'] . ' succefully subscripted'), ENT_QUOTES));

	}

	/**
	 * @param $post_data
	 */
	public function setInputPostData($post_data){
		$this->post_data = $post_data;
	}

	/**
	 *
	 */
	private function checkInputParams(){
		if(empty($this->post_data['id'])){
			$this->ajaxMes('empty $_POST[\'id\']');
		}
		$this->post_data['id']*=1;


		if(empty($this->post_data['hash_id']) || strlen($this->post_data['hash_id'])!== 40){
			$this->ajaxMes('empty or invalid  $_POST[\'hash_id\'] (' .$this->post_data['hash_id'] . ')' );
		}

		if($this->post_data['hash_id'] !== sha1($this->post_data['id'] . '39NeSeBANo6Lqfe7lOvmL9c24NTdi0lvwOfZaHbt') ){
			$this->ajaxMes('Invalid hash ' . $this->post_data['hash_id'] . ' !== ' . sha1($this->post_data['id'] . '39NeSeBANo6Lqfe7lOvmL9c24NTdi0lvwOfZaHbt'));
		}

		if(empty($this->post_data['list_id'])){
			$this->ajaxMes('empty $_POST[\'list_id\']');
		}
		$this->post_data['list_id']*=1;


		if(Validator::validateEmail($this->post_data['email']) === false){
			$this->ajaxMes('empty or invalid $_POST[\'email\'] = ' . $this->post_data['email']);
		}

	}


	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	private function selectDbData($id){
		$pgsql = Postgres::getInstance();
		try{
			$data = $pgsql->query('SELECT api_key FROM amocrm_table WHERE user_id = $1 LIMIT 1', array($id));
			if($data === null){
				$this->ajaxMes('SELECT api_key FROM amocrm_table WHERE user_id = ' . $id . ' LIMIT 1 was return an empty array()');
			}
			return $data[0];
		} catch ( Exception $ex){
			$this->ajaxEx($ex);
		}
	}


	/**
	 * @param $api_key
	 * @param $data
	 */
	private function subscribeToEstis($api_key, $data){
		$estis = EstisApi::getInstance($api_key);
		$estis->estisApiQuery('/mailer/emails', 'POST', $data);

		$err = $estis->getErr();
		if($err !== ''){
			$this->estis_error = $err;
		}
	}



}