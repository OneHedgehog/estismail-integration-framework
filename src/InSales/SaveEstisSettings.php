<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 10/2/17
 * Time: 5:18 PM
 */

namespace Integration\InSales;

use Integration\Common\Event;
use Integration\Common\Postgres;

use Exception;

class SaveEstisSettings extends Event
{
    protected $system_dir = __DIR__;

    public function __construct()
    {
        parent::__construct();
    }

    public function exec()
    {

	    //check post params, if not valid id die(). Return true/false
        $_POST= $this->validatePostParams($_POST);

        if($_POST === null){
        	$this->redirectBack();
        }

        //update data, we get from Login page
        $this->updateDbEstisParams($_POST);
        $db_data = $this->selectEstisDbData();
        //set webhook in in_sales and update webhook_id in db. If can't update webhook_id, die
	    if(empty($db_data['webhook_id'])){
		    $webhook_id = $this->setWebhook($_POST['id'], $db_data['hash'], $db_data['shop']);
	    }

	    if(!empty($webhook_id )){
	    	$this->updateWebhookId($webhook_id, $_POST['id']);
	    }

       $this->redirectBack();
    }


	/**
	 * check $_POST params
	 * if !valid req params die()
	 * if !valid not req params, return null
	 * @paran $post_data $_POST array()
	 * @return $post_data array() $_POST || null
	 */
    private function validatePostParams($post_data)
    {
        // autoIn id
        $post_data['id']*=1;
        if ( empty($post_data['id'])) {
            $this->logMes('$_POST[\'id\'] are empty or not int, $_POST[\'id\'] = (' . $post_data['id'] . ')');
        }

        $crypt_id = sha1('x9KRMg2rmitUTkviVQHxoOuD0PmTOmKatNWPJ7gm' . $post_data['id']);
        if ($post_data['id_hash'] !== $crypt_id) {
            $this->logMes('$_POST[\'id_hash\'] is\'nt equal to $crypt_id, $_POST[\'id_hash\'] = (' . $post_data['id_hash'] . ')');
        }

        if(!empty($post_data['double_opt_in']) && $post_data['double_opt_in'] !=1){
			$this->logMes('Invalid $_POST[\'double_opt_in\'] value =  ' . json_encode($post_data['double_opt_in'], ENT_QUOTES));
        }

        // api_key & list
	    if ( empty($_POST['list'])) {
		    return null;
	    }

        return $post_data;
    }

	/**
	 * select all req data from in_sales db
	 * $data['hash'] for api queris
	 * $data['webhook_id'] for check, if webhook is setted
	 * @return array
	 */
    private function selectEstisDbData(){
    	$pgsql = Postgres::getInstance();
    	try{
    		$data = $pgsql->query("SELECT hash, webhook_id, shop FROM in_sales WHERE id=$1 LIMIT 1", array($_POST['id']));
    		if(!$data){
    			$this->logMes("SELECT hash, webhook_id, shop FROM in_sales WHERE id=" . $_POST['id'] . "LIMIT 1 was return emty array()");
		    }
		    return $data[0];
	    } catch (Exception $ex){
            $this->logEx($ex);
	    }
	}

	/**
	 * update estis settings, we gaet form $_POST
	 * if can't update, die()
	 * @param $post_data
	 */
    private function updateDbEstisParams($post_data)
    {
        $pgsql = Postgres::getInstance();
        try {
            $pgsql->query("UPDATE in_sales SET list_id = $1, double_opt_in = $3 WHERE id = $2", array($post_data['list'], $post_data['id'], $post_data['double_opt_in'][0]));
        } catch (Exception $ex) {
            $this->logEx($ex);
        }
    }

    /**
     * @param $id ( integer ) ( id fpr db queries )
     * @param $hash ( string ) ( hash for InSales api queries)
     * @return $res['id'] (int) ( id )
     **/
    private function setWebhook($id, $hash, $shop)
    {

        $Insales = InSalesApi::getInstance();
        $method = '/admin/webhooks.json';
        $params = [
            'webhook' => [
                'address' => ESTIS_HOST_NAME . '/integration/insales/subscribed/?id=' . $id . '&id_hash=' . sha1($id . 'iM92UFpb1YbU5U5HC1vWdQm8cdAaQhUddHT730uX'),
                'topic' => 'orders/create',
                'format_type' => 'json'
            ]
        ];
        $res = $Insales->inSalesApiQuery($hash, $method, $shop, "POST", $params);
        $res = json_decode($res, 1);


        if (isset($res['id'])) {
        	return $res['id'];

        }
        return 0;
    }

    //update webhook id in our db, if can't die()
	/**
	 * @param int $webhook_id ( webhook id )
	 * @param int $id (id for db )
	 * if can't update, die
	 */
    private function updateWebhookId($webhook_id, $id){
	    $pgsql = Postgres::getInstance();
	    try {
		    $pgsql->query("UPDATE in_sales SET webhook_id = $1 WHERE id = $2", array($webhook_id, $id));
	    } catch (Exception $ex) {
            $this->logEx($ex);
	    }
    }
}