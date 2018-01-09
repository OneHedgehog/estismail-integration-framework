<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 9/25/17
 * Time: 1:23 PM
 */

namespace Integration\InSales;

use Exception;
use Integration\Common\Event;
use Integration\Common\Postgres;

class Install extends Event
{
    private $secret_app_key = '24e05f484d05826e681dc93598f0e0bc';

    public function __construct()
    {
        parent::__construct();
    }

    public function exec()
    {
    	//validate $_GET from InSales. Die if params are not valid
        $_GET =$this->validateInsalesInstallParams($_GET);
        //make ins_sales password param
	    $hash = md5($_GET['token'] . $this->secret_app_key);
	    //create new raw in db. Die if fail with INSERT query
        $this->saveShopParams($hash);
    }

	/**
	 * validate $_GET input params
	 * if not valid, die()
	 * @param $get_params $_GET array()
	 * @return $get_params $_GET array()
	 */
    private function validateInsalesInstallParams($get_params)
    {
    	// token
        if (empty($get_params['token']) || strlen($get_params['token']) !== 32) {
            $this->logMes('Invalid or empty install $_GET[\'token value\'] = (' . $get_params['token'] . ')');
        }

        // insales_id
	    $get_params['insales_id']*=1;
	    if (empty($get_params['insales_id'])) {
		    $this->logMes('$_GET[\'insales_id\'] are empty or not int, $_GET[\'insales_id\'] = (' . $get_params['insales_id'] . ')');
	    }

	    // shop
        if (empty($_GET['shop'])) {
            $this->logMes('empty $_GET[\'shop\'] = (' . $get_params['shop'] . ')');
        }
        // check html symbols
        $get_params['shop'] = htmlentities( $get_params['shop'], ENT_QUOTES);

		return $get_params;
    }


	/**
	 * @param $query_password
	 * add a new row to db
	 */
    private function saveShopParams($query_password)
    {
        $pgsql = Postgres::getInstance();
        try {
            $pgsql->query("INSERT INTO in_sales ( shop, hash, insales_id) VALUES ($1 , $2 , $3)", array($_GET['shop'], $query_password, $_GET['insales_id']));
        } catch (Exception $ex) {
            $this->logEx($ex);
        }
    }
}