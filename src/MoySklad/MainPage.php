<?php

namespace Integration\MoySklad;

use Integration\Common\EventSession;
use Integration\Common\Postgres;
use Integration\Common\EstisApi;
use Integration\MoySklad\MoySkladApi;
use Exception;

class MainPage extends EventSession
{
    protected $view = 'moysklad';
    protected $system_dir = __DIR__;

    public function __construct()
    {
        parent::__construct();
    }

    public function exec()
    {
    	//$this->validateSessionId();

        $this->data['login_alert_status'] = 0;
        $this->data['api_key_alert_status'] = 0;

	    if(!empty($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] === ESTIS_HOST_NAME . '/integration/moysklad/main-page/'){
		    $this->data['login_alert_status'] = 2; //Connection failed
	    };

        // $si - session_instance
        $si = EventSession::getInstance();
        $si->startSession();
        $user_id = $si->__get('user_id');

        if ($user_id !== null) {

            $this->data['user_db'] = $this->getUserData($user_id);

            $this->data['login_alert_status'] = 1;

            // Part of connecting to estismail Api
            $api_key = $this->data['user_db']['api_key'];
            if (!preg_match("/^[a-z\d]{40}$/i", $api_key)) {

                $this->data['api_key_alert_status'] = 0;

                //connection failed status
                if (!empty($api_key)) {
                    $this->data['api_key_alert_status'] = 2;
                }

                //to collect api_key connection & list data in one method
                $this->render();
            }

            $this->data['estis'] = $this->getDataFromEstis($api_key);
            $this->data['api_key_alert_status'] = 2;

            if (!empty($this->data['estis']['user'])) {
                $this->data['api_key_alert_status'] = 1;
            };
        }

        if (isset($_POST['logout']) == true) {
            $si->destroy();
            $this->redirectBack();
        }

        if (isset($_POST['deactivate']) == true) {
            $this->deleteMoyskladWebhook($this->data['user_db']);
            $this->cleanDataBase($user_id);
            $si->destroy();
            $this->redirectBack();
        }

        $this->render();
    }

	/**
	 * @param $user_id
	 * get ALL data from db, die if fail
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
	 * @param $api_key string
	 * loop through estis methods
	 *  if (!res) return
	 * @return array
	 */
    private function getDataFromEstis($api_key)
    {
        //key must be the same, as estis method keys
        $method = [
            'user' => '/mailer/users',
            'lists' => '/mailer/lists'
        ];

        //response from estis Api
        $data = array();
        $estis_instance = EstisApi::getInstance($api_key);

        foreach ($method as $key => $value) {
            $response = $estis_instance->estisApiQuery($value);

            if ($response === false) {
                return $data;
            }
            $data[$key] = $response[$key];
        }

        return $data;
    }

    private function cleanDataBase($user_id)
    {
        $pgsql = Postgres::getInstance();
        try {
            $pgsql->query("DELETE FROM estis_moysklad_table WHERE user_id = $1", array($user_id));
        } catch (Exception $ex) {
            $this->logEx($ex);
        }
    }

    private function deleteMoyskladWebhook($user_data)
    {
        $moysklad_instance = MoySkladApi::getInstance($user_data['login'], $user_data['password']);
        $url = 'entity/webhook/' . $user_data['webhook_id'];
        $response = $moysklad_instance->moySkladQuery($url, $params = array(), 'DELETE');
        return json_decode($response, true);
    }
}