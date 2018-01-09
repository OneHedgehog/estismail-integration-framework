<?php

namespace Integration\MoySklad;

use Integration\Common\EventSession;
use Integration\Common\Postgres;
use Integration\Common\EstisApi;
use Integration\MoySklad\MoySkladApi;
use Integration\Common\Validator;
use Exception;

class SubscribeAll extends EventSession
{
    protected $system_dir = __DIR__;
    private $user_db = array();

    public function __construct()
    {
        parent::__construct();
    }

    public function exec()
    {
        // $si - session_instance
        $si = EventSession::getInstance();
        $si->startSession();
        $user_id = $si->__get('user_id');

        if ($user_id !== null) {
            $this->user_db = $this->getUserData($user_id);
            $this->counterpartiesIterate($this->user_db);
        }
        $this->redirectBack();
    }

	/**
	 * @param $user_id
	 * get all data from ecwid db
	 * @return mixed
	 */
    private function getUserData($user_id)
    {
        $psql = Postgres::getInstance();
        try {
            $data = $psql->query("SELECT * FROM estis_moysklad_table WHERE user_id = $1 LIMIT 1", array($user_id))[0];
            if ($data === null) {
                $this->logMes('SELECT * FROM estis_moysklad_table WHERE user_id =' . $user_id . ' LIMIT 1 was return emty array()');
            }
        } catch (Exception $ex) {
            $this->logEx($ex);
        }
        return $data;
    }


	/**
	 * @param $user_params
	 * @param int $i
	 * subscribe all counterparties to estis
	 * @return bool
	 */
    private function counterpartiesIterate($user_params, $i = 0)
    {
        while (true) {

            // limit counterparties per page
            $limit = 100;
            $method = 'entity/counterparty';
            $params = array(
                'limit' => $limit,
                'offset' => $i,
            );

            $moysklad_instance = MoySkladApi::getInstance($user_params['login'], $user_params['password']);
            $response = $moysklad_instance->moySkladQuery($method, $params, "GET");
            $response = json_decode($response, 1);
            $counterparties = $response['rows'];

            if (empty($counterparties)) {
                return false;
            }

            foreach ($counterparties as $person) {

                if (isset($person['email']) && filter_var($person['email'], FILTER_VALIDATE_EMAIL)) {

                    if (!isset($person['name'])) {
                        $person['name'] = '';
                    }

                    if (!isset($person['phone'])) {
                        $person['phone'] = '';
                    }

                    if (!isset($person['legalAddress'])) {
                        $person['legalAddress'] = '';
                    }

                    $this->subscribeCounterparty($person, $user_params);
                }
            }
            $i += $limit;
        }
    }

	/**
	 * @param $person
	 * @param $user_params
	 *
	 * @return string
	 */
    private function subscribeCounterparty($person, $user_params)
    {

    	//stop, if invalid Email
    	if(Validator::validateEmail($person['email']) === false){
			return false;
	    }

        $one_person = array(
            'email' => $person['email'],
            'name' => htmlentities($person['name']),
            'phone' => htmlentities($person['phone']),
            'city' => htmlentities($person['legalAddress'])
        );
        $one_person['list_id'] = $user_params['list_id'];

        if (!empty($user_params['double_opt_in'])) {
            $one_person['double_opt_in'] = 1;
        }

        $estis_instance = EstisApi::getInstance($user_params['api_key']);
        $response = $estis_instance->estisApiQuery('/mailer/emails', "POST", $one_person);

        return $response;
    }
}