<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 9/29/17
 * Time: 3:24 PM
 */

namespace Integration\Ecwid;

use Integration\Common\Event;
use Integration\Common\Postgres;
use Integration\Common\EstisApi;
use Integration\Ecwid\ApiConnect;
use Integration\Common\Validator;

use Exception;

class Webhook extends Event
{
    private $raw_data = array();
    //all data form db, cause that class something like webhooks router
    private $db_data = array();

    public function __construct()
    {
        parent::__construct();
    }

    public function setPostRawData($raw_data)
    {
        if (!empty($raw_data)) {
        	try{
		        $this->raw_data = json_decode($raw_data, 1);
	        } catch ( Exception $ex){
        		$this->logEx($ex);
	        }

        }

        return array();
    }

    public function exec()
    {
        $raw_data = $this->raw_data;


	    $this->db_data = $this->getAllDbData();

        if (empty($raw_data)) {
            $this->logMes('Empty $HTTP_RAW_POST_DATA');
        }

        //check common input params
        $this->checkCommonInputParams($raw_data);


        //collect data from ecwid db
        $this->db_data = $this->getAllDbData();

        //checking webhook type
        $this->checkWebHookType($raw_data['eventType']);
    }

    /**
     * @param $event_type routing parsed raw_data from index
     */
    private function checkWebHookType($event_type)
    {
        switch ($event_type) {
            case("order.created"): {
                $this->execOrderMethod();
            }
                break;
            case("application.uninstalled"): {
                $this->execUninstallMethod();
            }
                break;
            case("application.installed"): {
                $this->execInstallMethod();
            }
                break;
        }
    }

	/**
	 * Subscribe to estis, when Ecwid order is created ( notified by webhook )
	 * CARE! Ecwid webhooks have a delay ( may be for 10-15 minutes )
	 * @return false if fail
	 */
    private function execOrderMethod()
    {
        if (empty($this->db_data['list_id'])) {
            $this->logMes('Empty $this->db_data[\'list_id\'] with storeId = ' . $this->raw_data['storeId']);
        }

        $ecwid_api = EcwidApi::getInstance();
        $api_key = $this->db_data['api_key'];
        $store_id = $this->raw_data['storeId'];

        $method = 'orders';
        $params = array(
            'token' => $this->db_data['token']
        );

        $id = $this->raw_data['entityId'];
	    $res = $ecwid_api->ecwidApiQuery($store_id, $method, $params, $id);


	    if(empty($res)){
	    	return false;
	    }

        //if invalid input res
        try{
	        $res = json_decode($res, 1);
        } catch ( Exception $ex){
        	$this->logEx($ex);
        }


        if ($res['extraFields']['subscribe'] === 'Yes') {
            $estis = EstisApi::getInstance($api_key);


            if(Validator::validateEmail($res['email'] === false)){
            	return false;
            }

            if(Validator::validateIp($res['ipAddress']) === false){
	            $res['ipAddress'] = 0;
            }


            $user = array(
                'email' => $res['email'],
                'list_id' => $this->db_data['list_id'],
                'ip' => $res['ipAddress'],
                'city' => htmlentities($res['billingPerson']['city']),
                'phone' => htmlentities($res['billingPerson']['phone']),
                'activation_letter' => 1
            );

            if (!empty($this->db_data['double_opt_in'])) {
                $user['activation_letter'] = 0;
            }


            $response = $estis->estisApiQuery('/mailer/emails', "POST", $user);



            $subscription_arr = array(
                'email' => $user['email'],
                'success' => 0
            );

            if ($response !== false) {
                $subscription_arr['success'] = 1;
            }

            $subscription_arr = json_encode($subscription_arr);

            $pgsql = Postgres::getInstance();
            try {
                $pgsql->query("UPDATE estis_ecwid_app SET last_subscriber_email = $1", array($subscription_arr));
            } catch (Exception $ex) {
                $this->logEx($ex);
            }
        }
    }

	/**
	 * Uninstall webhook processing
	 * Clean the db
	 * Webhooks destroy automatically
	 */
    private function execUninstallMethod()
    {
        $pgsql = Postgres::getInstance();
        try {
            $pgsql->query("DELETE FROM estis_ecwid_app WHERE ecwid_id = $1", array($this->raw_data['storeId']));
        } catch (Exception $ex) {
            $this->logEx($ex);
        }
    }


	/**
	 * Install webhook processing
	 * Set the db. Create row with ecwid_id
	 * Webhooks set-up automatically
	 */
    private function execInstallMethod()
    {
        $pgsql = Postgres::getInstance();
        try {
            $pgsql->query("INSERT INTO estis_ecwid_app ( ecwid_id) VALUES ($1)", array($this->raw_data['storeId']));
        } catch (Exception $ex) {
            $this->logEx($ex);
        }
    }

	/**
	 * get All db data in order processing
	 * @return mixed ( db array )
	 */
    private function getAllDbData()
    {
        $pgsql = Postgres::getInstance();
        try {
        	//We shouldn't check om empty data, because duriing Install/Uninstall we don't have it
            $data = $pgsql->query("SELECT * FROM estis_ecwid_app WHERE ecwid_id = $1", array($this->raw_data['storeId']));
            //DON'T SET EXCEPTION FOR EMPTY DATA FROM DB HERE. IN THIS CASE WE DON'T NEED IT
            return $data[0];
        } catch (Exception $ex) {
            $this->logEx($ex);
        }
    }

	/**
	 * checking $HTTP_RAW_POST_DATA
	 * @param $raw_input
	 */
    private function checkCommonInputParams($raw_input)
    {
        if (empty($raw_input['storeId'])) {
            $this->logMes('Empty $HTTP_RAW_POST_DATA[\'storeId\']');
        } elseif ($raw_input['storeId'] != $raw_input['storeId'] * 1) {
            $this->logMes('Invalid $HTTP_RAW_POST_DATA[\'storeId\']');
        }

        //Don't mind about eventId name, it's a string ( not int )
        if (empty($raw_input['eventId'])) {
            $this->logMes('Empty $HTTP_RAW_POST_DATA[\'eventId\'] in shop with storeId=' . $raw_input['storeId']);
        }

        if (empty($raw_input['eventType'])) {
            $this->logMes('Empty $HTTP_RAW_POST_DATA[\'eventType\'] in shop with storeId=' . $raw_input['storeId']);
        }

        if (empty($raw_input['eventCreated'])) {
            $this->logMes('Empty $HTTP_RAW_POST_DATA[\'eventCreated\'] in shop with storeId=' . $raw_input['storeId']);
        } elseif ($raw_input['eventCreated'] != $raw_input['eventCreated'] * 1) {
            $this->logMes('Invalid $HTTP_RAW_POST_DATA[\'eventCreated\'] in shop with storeId=' . $raw_input['storeId']);
        }

        if (empty($raw_input['entityId'])) {
            $this->logMes('Empty $HTTP_RAW_POST_DATA[\'entityId\'] in shop with storeId=' . $raw_input['storeId']);
        } elseif ($raw_input['entityId'] != $raw_input['entityId'] * 1) {
            $this->logMes('Invalid $HTTP_RAW_POST_DATA[\'entityId\'] in shop with storeId=' . $raw_input['storeId']);
        }
    }
}