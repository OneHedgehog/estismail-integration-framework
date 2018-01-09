<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 10/31/17
 * Time: 5:02 PM
 */

namespace Integration\MoySklad;

use Integration\Common\Event;
use Integration\MoySklad\MoySkladApi;
use Integration\Common\Postgres;
use Integration\Common\EstisApi;
use Integration\Common\Validator;

use Exception;


class Webhook extends Event
{
    private $raw_data = array();

	public function __construct() {
		parent::__construct();
	}

    public function exec()
    {
	    $user_params = $this->validateGetParams($_GET);

        $counterparty_url = $this->raw_data['events'][0]['meta']['href'];

        $user_data = $this->getUserData($user_params['id']);

        $counterparty = $this->getCounterparty($user_data, $counterparty_url);

        $this->subscribeCounterparty($counterparty, $user_data);
    }


	/**
	 * @param $HTTP_RAW_POST_DATA
	 * save $HTTP_RAW_POST_DATA to global var
	 */
    public function setRawData($HTTP_RAW_POST_DATA)
    {
        $this->raw_data = json_decode($HTTP_RAW_POST_DATA, 1);
    }

	/**
	 * @param $get_params
	 * validate get params
	 * @return mixed
	 */
    public function validateGetParams($get_params)
    {

	    $hash = sha1('M0iCJGePNKmY4TKFWof0qdNaG7u20rQmlyDiQNMn' . $get_params['id']);
	    if($hash !== $get_params['hash']){
	    	$this->logMes('Invalid hash ' . $hash . ' = ' . $get_params['hash'] );
	    }
        return $get_params;
    }

	/**
	 * @param $user_id
	 * get all required db data
	 * @return mixed
	 */
    private function getUserData($user_id)
    {
        $psql = Postgres::getInstance();
        try {
            $data = $psql->query("SELECT * FROM estis_moysklad_table WHERE user_id = $1 LIMIT 1", array($user_id));
            if ($data === null) {
                $this->logMes('SELECT * FROM estis_moysklad_table WHERE user_id =' . $user_id . ' LIMIT 1 was return empty array()');
            }
        } catch (Exception $ex) {
            $this->logEx($ex);
        }
        return $data[0];
    }



	/**
	 * @param $login_params
	 * @param $url
	 * get counterparty from api
	 * @return mixed ( JSON array from api)
	 */
    private function getCounterparty($login_params, $url)
    {
        $moysklad_instance = MoySkladApi::getInstance($login_params['login'], $login_params['password']);
        $response = $moysklad_instance->moySkladQuery( $url, '', "GET");
        return json_decode($response,1);
    }

	/**
	 * @param $person
	 * @param $user_data
	 * subscribe ONE_USER
	 * @return bool|string
	 */
    private function subscribeCounterparty($person, $user_data)
    {

	    if(Validator::validateEmail($person['email']) === false){
	    	return false;
	    }

        $one_person = array(
            'email' => $person['email'],
	        'list_id' => $user_data['list_id'],
	        'double_opt_in' => $user_data['double_opt_in']
        );


	    if (isset($person['name'])) {
		    $one_person['name'] = htmlentities($person['name']);
	    }

	    if (isset($person['phone'])) {
		    $one_person['phone'] = htmlentities($person['phone']);
	    }

	    if (isset($person['legalAddress'])) {
		    $one_person['city'] = htmlentities($person['legalAddress']);
	    }

        $estis_instance = EstisApi::getInstance($user_data['api_key']);
        $response = $estis_instance->estisApiQuery('/mailer/emails', "POST", $one_person);

        return $response;
    }

}