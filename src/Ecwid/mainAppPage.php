<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 10/24/17
 * Time: 11:24 AM
 */

namespace Integration\Ecwid;

use Integration\Common\Event;
use Integration\Common\Postgres;
use Integration\Common\EstisApi;
use Exception;


class mainAppPage extends Event{

	public function __construct() {
		parent::__construct();
	}


	public function exec(){
		$local_post = $this->validateEcwidInputParams($_POST);

		//save token to db
		$this->updateReqParams($local_post['token'], $local_post['shopId']);

		//get data from db
		$this->data['db'] = $this->getEcwidDbData($local_post['shopId']);

		$this->data['alert_data']= array(
			'0'=>array(
				'class'=> 'alert-warning',
				'head'=>'Not yet connected :|',
				'mes'=>'Connect with estismail usin your api-key in estismail account',
			),
			'1'=>array(
				'class'=>'alert-success',
				'head'=>'Connected :)',
				'mes'=>'Succefully connected to estismail. Start with \'Save Setting\' tab'
			),
			'2'=>array(
				'class'=>'alert-error',
				'head'=>'Connection failed :(',
				'mes'=> 'Can\'t connect to estismail. Try again later or contact to support'
			)
		);

		//get data from estis
		$api_key = $this->data['db']['api_key'];

		//alert status = Connection failed
		$status = 2;
		if(preg_match("/^[a-z\d]{40}$/i", $api_key)){
			//to collect api_key connection & list data in one method
			$this->data['estis'] = $this->getDataFromEstis($api_key);
		}

		if(!empty($this->data['estis'])){
			$status = 1; // connected
		}

		if(empty($this->data['db']['api_key'])){
			$status = 0; //not yet connected
		}

		//status for alert subs
		$this->data['alert_status'] = $status;

		die(json_encode($this->data, ENT_QUOTES));

	}

	/**
	 * @param $post_data
	 * validate Input data
	 * @return mixed
	 */
	private function validateEcwidInputParams($post_data){

		if(empty($post_data['post_data'])){
			$this->ajaxMes('empty $_POST[\'post_data\']');
		}


		try{
			$post_data['post_data'] = json_decode($post_data['post_data'],1);
		} catch ( Exception $ex){
			$this->ajaxEx($ex);
		}


		$post_data['post_data']['shopId'] *=1;
		if(empty($post_data['post_data']['shopId'])){
			$this->ajaxMes('empty $_POST[\'post_data\'][\'shopId\']');
		}

		//tokens have different length
		if(empty($post_data['post_data']['token'])){
			$this->ajaxMes('empty $_POST[\'post_data\'][\'token\']');
		}

		return $post_data['post_data'];
	}

	/**
	 * @param $token
	 * Save token ( We use it webhook api queries )
	 * @param $id
	 */
	private function updateReqParams($token, $id){
		$post = Postgres::getInstance();
		try{
			$post->query("UPDATE estis_ecwid_app SET token = $1 WHERE ecwid_id = $2", array($token, $id));
		} catch ( Exception $ex ){
			$this->ajaxEx($ex);
		}
	}

	/**
	 * @param $id
	 * get all data from db
	 * @return bool
	 */
	private function getEcwidDbData($id){
		$post = Postgres::getInstance();
		try{
			$data =  $post->query("SELECT * FROM estis_ecwid_app WHERE ecwid_id = $1 LIMIT 1", array($id));
			if(empty($data)){
				$this->ajaxMes("SELECT * FROM estis_ecwid_app WHERE ecwid_id = " . $id . " LIMIT 1 was return an empty array()");
			}
			return $data[0];

		} catch ( Exception $ex ){
			$this->ajaxEx($ex);
		}
		return false;

	}

	/**
	 * @param $api_key
	 * get user & lists from estis
	 * @return null | array()
	 */
	private function getDataFromEstis($api_key){
		$estis = EstisApi::getInstance($api_key);
		$method = '/mailer/users';
		$res = $estis->estisApiQuery($method);

		if(!empty($res)){
			$return_arr['user'] = $res['user'];
			$method = '/mailer/lists';
			$res = $estis->estisApiQuery($method);
			if(!empty($res)){
				$return_arr['lists'] = $res['lists'];
			}
			return $return_arr;
		}

		return null;
	}

}