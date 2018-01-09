<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 10/2/17
 * Time: 5:17 PM
 */

namespace Integration\InSales;

use Integration\Common\Event;
use Integration\Common\Postgres;
use Exception;

class SaveApiKey extends Event
{
	protected $system_dir = __DIR__;

    public function __construct()
    {
        parent::__construct();
    }

    public function exec()
    {
		//set validation value ( @bool )
    	$_POST = $this->validatePostQueryParams($_POST);

        if($_POST !== null){
        	//update api_key in db. If fail, die()
	        $this->saveApiKey($_POST['api_key'], $_POST['id']);
        }

       $this->redirectBack();
    }


	/**
	 * check $_POST params. If !valid req params die()
	 * if (!valid) not req params, return false
	 * @param array $post_data $_POST
	 * @return array $post_data
	 */
    private function validatePostQueryParams($post_data)
    {

        // autoinc id
	    $post_data['id']*=1;
        if (empty($post_data['id'])) {
            $this->logMes('$_POST[\'id\'] is empty or invalid, $_POST[\'id\'] = (' . $post_data['id'] . ')');
        }

        $crypt_id = sha1('RYqxUsnCkpQpQMyfv23vopsBBE72aRV6LQ0quLDI' . $post_data['id']);
        if ($post_data['id_hash'] !== $crypt_id) {
            $this->logMes('$_POST[\'id_hash\'] is\'nt equal to $crypt_id, $_POST[\'id_hash\'] = (' . $post_data['id_hash'] . ')');
        }

	    // Estis Api Key
	    if (empty($post_data['api_key']) || strlen($post_data['api_key']) > 40) {
		    return null;
	    }

		return $post_data;
    }


	/**
	 * Save api_key to db. If failed, die()
	 * @param $api_key
	 * @param $id
	 */
    private function saveApiKey($api_key, $id)
    {
        $pgsql = Postgres::getInstance();
        try {
            $pgsql->query("UPDATE in_sales SET api_key = $1 WHERE id = $2", array($api_key, $id));
        } catch (Exception $ex) {
            $this->logEx($ex);
        }
    }
}