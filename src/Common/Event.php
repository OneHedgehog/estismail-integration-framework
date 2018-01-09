<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 9/25/17
 * Time: 1:00 PM
 */

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