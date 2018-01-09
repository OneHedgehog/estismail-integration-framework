<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 9/25/17
 * Time: 1:27 PM
 */

namespace Integration\InSales;

use Integration\Common\Event;
use Integration\Common\Postgres;
use Integration\Common\EstisApi;
use Integration\Common\Validator;

use Exception;

class Subscribe extends Event
{

    protected $system_dir = __DIR__;

    private $insales_data_from_db = array();//data, we get in selectInsalesDataFromDb method

    private $raw_data = []; //we call setRawData in router, so it supposed $HTTP_RAW_POST_DATA to be here

    public function __construct()
    {

        parent::__construct();

    }

    public function exec()
    {
        //we must get insales id in GET ( we set it up, when we created a webhook ). Die if failed
        $_GET = $this->validateGetParams($_GET);
        //Select all data we need for subscription. Die if failed
        $this->insales_data_from_db = $this->selectInsalesDataFromDb();

        //number of order, we update if fail
        $order_num = $this->raw_data['number'];
        //subscribe to estis, when user bought some
        $this->subscribe($this->raw_data['client'], $_GET['id'], $order_num);
    }


	/**
	 * @param $get_data $_GET array()
	 * validate insales id which we set id, when webhook was created
	 * @return $_GET array()
	 */
    private function validateGetParams($get_data)
    {
        // insales_id
        $get_data['id'] *= 1;
        if (empty($get_data['id'])) {
            $this->logMes('$_GET[\'id\'] is empty or invalid, $_GET[\'id\'] = (' . $get_data['id'] . ')');
        }

        $id_hash = sha1($get_data['id'] . 'iM92UFpb1YbU5U5HC1vWdQm8cdAaQhUddHT730uX');

        if($id_hash !== $get_data['id_hash']){
        	$this->logMes('$id_hash !== $_GET[\'id_hash\'] (' . $id_hash . '!==' . $get_data['id_hash'] . ' )');
        }


        return $get_data;
    }


    /**
     * @param $raw_data
     * NOTICE we call this method in router. And this method executes first
     * setting up $HTTP_RAW_POST_DATA && validate
     */
    public function setRawData($raw_data)
    {


        if (empty($raw_data)) {
	        $this->logMes('empty $HTTP_RAW_POST_DATA');
        }

        //if not json;
        try{
	        $raw_data = json_decode($raw_data, true);
        } catch ( Exception $ex){
            $this->logMes($ex);
        }


        if(!isset($raw_data['number']) || !isset($raw_data['client'])){
	        $this->logMes('Invalid order object');//here we also check subscribe value, cause it's required in $this->raw_data['client']
        }
        $client = $raw_data['client'];
        if(!isset($client['subscribe'])){
	        $this->logMes('req param $HTTP_RAW_POST_DATA[\'client\'][\'subscribe\']');
        }

        if(!isset($client['email'])){
	        $this->logMes('req param $HTTP_RAW_POST_DATA[\'client\'][\'email\']');
        }

        $this->raw_data = $raw_data;
        return;
    }

    //select all insales data from db, which we need for subscription
    private function selectInsalesDataFromDb()
    {
        $pgsql = Postgres::getInstance();
        try {
            $data = $pgsql->query("SELECT api_key, list_id, double_opt_in, id FROM in_sales WHERE id = $1 LIMIT 1", array($_GET['id']));
            if (!$data) {
                $this->logMes("SELECT api_key, list_id, double_opt_in, id FROM in_sales WHERE id = " . $_GET['id'] . " was return an emty array()");
            }
            return $data[0];
        } catch (Exception $ex) {
            $this->logEx($ex);
        }
    }


    /**
     * @param $order_data
     * @param $id
     * @param $order_num
     * subscribe estis user
     */
    private function subscribe($order_data, $id, $order_num)
    {

	    $last_subscribe_info = array(
		    'email' => $order_data['email'],
		    'order_id' => $order_num,
		    'valid' => false
	    );

	    //Save if invalid req params
	    if(Validator::validateEmail($order_data['email']) !== true){
			$this->saveClientStatistic($last_subscribe_info, $id);
			return;
	    }

        //(data from $HTTP_RAW_POST_DATA) collect estis /mailer/emails/ POST query params
        $user_info = [
            'email' => $order_data['email'],
            'name' => htmlentities($order_data['name']),
            'phone' => htmlentities($order_data['phone'])
        ];


	    if(Validator::validateIp($order_data['ip_addr']) === true){
		    $user_info['ip'] = $order_data['ip_addr'];
	    };



        //data from insales db) collect estis /mailer/emails/ POST query params
        $user_info['list_id'] = $this->insales_data_from_db['list_id'];
        $user_info['activation_letter'] = 1;

        if (empty($this->insales_data_from_db['double_opt_in'])) {
            $user_info['activation_letter'] = 0;
        }


	    // [0] index, cause we have array of rows. And we should to select only first row
	    $api_key = $this->insales_data_from_db['api_key'];
	    $api_method = '/mailer/emails';

	    //subscription
        $estis = EstisApi::getInstance($api_key);
        $res = $estis->estisApiQuery($api_method, $type = "POST", $user_info);

        if($res){
	        $last_subscribe_info['valid'] = true;
        }

	    $this->saveClientStatistic($last_subscribe_info, $id);

        $estis_mes = $estis->getErr();
        if($estis_mes !== ""){
        	$this->logMes($estis_mes);
        }
    }


    private function saveClientStatistic($last_subscribe_info, $id){
	    $last_subscribe_info = json_encode($last_subscribe_info, ENT_QUOTES);
	    //show to user last email subscribed
	    $pgsql = Postgres::getInstance();
	    try {
		    $pgsql->query("UPDATE in_sales SET last_subscribe_email = $1 WHERE id = $2", array($last_subscribe_info, $id));
	    } catch (Exception $ex) {
		    $this->logEx($ex);
	    }
    }

}