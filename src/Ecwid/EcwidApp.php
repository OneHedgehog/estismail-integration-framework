<?php
namespace Integration\Ecwid;

use Integration\Common\Event;


class EcwidApp extends Event {

	protected $view = 'ecwid';
	protected $system_dir = __DIR__;

	public function __construct() {
		parent::__construct();
	}

	public function exec(){
		$this->render();
	}

}