<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 9/25/17
 * Time: 1:23 PM
 */

namespace Integration\InSales;

use Integration\InSales\InSalesApi;
use Integration\Common\Postgres;
use Integration\Common\Event;
use Exception;

class Uninstall extends Event
{
    protected $system_dir = __DIR__;

    private $insales_params_from_db = array();

    public function __construct()
    {
        parent::__construct();
    }

    public function exec()
    {
    	//validate $_GET params, die if not valid
        $_GET = $this->validateInsalesGetParams($_GET);
        //if we have empty fail, die
        $this->insales_params_from_db = $this->getInsalesParamsFromDb();
        //die, if compare doesn't match each other
        $this->compareInsalesInputDataWithDb($this->insales_params_from_db);

        //delete Webhook by id from insales API ( not db )
        $this->deleteHook($this->insales_params_from_db['webhook_id']);

        //clean DB with our Id. If fail, die
        $this->cleanDataBase($this->insales_params_from_db['id']);
    }

	/**
	 * @param $get_params $_GET
	 * validate get params
	 * @return mixed $get_params
	 */
    private function validateInsalesGetParams($get_params)
    {
        // validate token
        if (empty($get_params['token']) || strlen($get_params['token']) !== 32) {
            $this->logMes('Invalid or empty uninstall $_GET[\'token value\'] = (' . $get_params['token'] . ')');
        }

        // insales_id
        $get_params['insales_id'] *= 1;
        if (empty($get_params['insales_id'])) {
            $this->logMes('$_GET[\'insales_id\'] are empty or not int, $_GET[\'insales_id\'] = (' . $get_params['insales_id'] . ')');
        }

        // shop
        if (empty($get_params['shop'])) {
            $this->logMes('Empty uninstall $_GET[\'shop\'] = (' . $get_params['shop'] . ')');
        }

        return $get_params;
    }

	/**
	 * Select Insales data from db by id.
	 * If can't select die().
	 * If select empty array die()
	 */
    private function getInsalesParamsFromDb()
    {
        $pgsql = Postgres::getInstance();
        try {
            $data = $pgsql->query("SELECT id, hash, shop, webhook_id  FROM in_sales WHERE insales_id = $1 LIMIT 1", array($_GET['insales_id']));
	        if($data === null){
	        	$this->logMes("SELECT id, hash, shop, webhook_id  FROM in_sales WHERE insales_id = ". $_GET['insales_id'] .  " LIMIT 1 was return an ampty array()");
	        }
	        return $data[0];
        } catch (Exception $ex) {
            $this->logEx($ex);
        }
    }

	/**
	 * @param array $data ( db data )
	 * compare $_GET input with data, we have on db
	 */
    private function compareInsalesInputDataWithDb($data)
    {
        // compare insales_id with db
        if ($_GET['shop'] != $data['shop']) {
            $this->logMes('Your shop doesn\'t equal the shop in the db $_GET[\'shop\'] value = (' . $_GET['shop'] . ' !=' .  $data['shop'] . ')');
        }


        // compare token with db
        if ($_GET['token'] != $data['hash']) {
            $this->logMes('Your token doesn\'t equal the token in the db $_GET[\'token\'] value = (' . $_GET['token'] . ')');
        }
    }


	/**
	 * @param int $webhook_id
	 * delete hook from InSales API
	 * if !webhook_id return
	 */
    private function deleteHook($webhook_id = 0)
    {
        if ($webhook_id === 0) {
            return;
        }
        $InSales = InSalesApi::getInstance();
        $method = '/admin/webhooks/' . $webhook_id . '.json';
        $InSales->inSalesApiQuery($_GET['token'], $method, $_GET['shop'], 'DELETE');
    }

	/**
	 * DELETE raw by id
	 * @param int $id ( id for db )
	 */
    private function cleanDataBase($id)
    {
        $pgsql = Postgres::getInstance();
        try {
            $pgsql->query("DELETE FROM in_sales WHERE id = $1", array($id));
        } catch (Exception $ex) {
            $this->logEx($ex);
        }
    }
}