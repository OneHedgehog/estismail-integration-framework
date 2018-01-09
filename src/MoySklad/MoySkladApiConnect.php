<?php

namespace Integration\MoySklad;

use Integration\Common\EventSession;
use Integration\Common\Postgres;
use Integration\MoySklad\MoySkladApi;
use Exception;

class MoySkladApiConnect extends EventSession
{
    protected $view = 'moysklad';
    protected $system_dir = __DIR__;

    public function __construct()
    {
        parent::__construct();
    }

    public function exec()
    {
        $login_params = $this->validateLoginParams($_POST);
        $response = $this->moyskladApiConnect($login_params['login'], $login_params['password']);
        $response = json_decode($response, 1);


        if (isset($response['errors'])) {
            $this->redirectBack();
        }

        $user_id = $this->selectUserDb($login_params['login']);

        //check, if we have some id
        if ($user_id === null) {
            $user_id = uniqid('ms');
            $this->createNewUser($login_params['login'], $login_params['password'], $user_id); //generate new db user
        }

        // $si - session_instance
        $si = EventSession::getInstance();
        $si->startSession();
        $si->__set('user_id', $user_id);

        //check db data ( don't sure we have to do it)
        if (!empty($user_id['user_id'])) {
            $si->__set('user_id', $user_id['user_id']);
        }

        $this->redirectBack();
    }


	/**
	 * @param $post_data
	 * validate params frm $_POST
	 * captha
	 * @return mixed
	 */
    private function validateLoginParams($post_data)
    {
        if (empty($post_data['login']) || empty($post_data['password'])) {
            $this->logMes('Empty $_POST[ \'login\' ] or empty $_POST[ \'password\' ]');
        }

        $post_data['login'] = htmlentities($post_data['login']);
        $post_data['password'] = htmlentities($post_data['password']);

        if (empty($post_data['g-recaptcha-response'])) {
            $this->logMes('Empty $_POST[ \'g-recaptcha-response\' ]');
        }
        // make request to verify captcha
        $captcha = $post_data['g-recaptcha-response'];
        $secret_key = "6LcXqTYUAAAAANV10tFwgt52Dq_LEHglRDrJZjc3";
        $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
        $ip = $_SERVER['REMOTE_ADDR'];

        $params = array(
            'secret' => $secret_key,
            'response' => $captcha,
            'remoteip' => $ip
        );
        $verify_url = $verify_url . '?' . http_build_query($params);

        $response = file_get_contents($verify_url);
        $response_keys = json_decode($response, true);

        if (intval($response_keys["success"]) !== 1) {
            $this->logMes('Someone is trying to change the captcha view key');
        }

        return $post_data;
    }


	/**
	 * @param $login
	 * @param $password
	 * test connection to moysklad
	 * @return array
	 */
    private function moyskladApiConnect($login, $password)
    {
        $moysklad_instance = MoySkladApi::getInstance($login, $password);
        $method = 'entity/counterparty';

        $response = $moysklad_instance->moySkladQuery($method);
        return $response;
    }


	/**
	 * @param $login
	 * get user_id from db
	 * @return mixed ( Number as a string )
	 */
    private function selectUserDb($login)
    {
        $psql = Postgres::getInstance();
        try {
            $data = $psql->query("SELECT user_id FROM estis_moysklad_table WHERE login = $1 LIMIT 1", array($login));
            return $data[0];
        } catch (Exception $ex) {
            $this->logEx($ex);
        }
    }

	/**
	 * @param $login
	 * @param $password
	 * @param $user_id
	 * create new user, if have not user_id
	 */
    private function createNewUser($login, $password, $user_id)
    {
        $psql = Postgres::getInstance();
        try {
            $psql->query("INSERT INTO estis_moysklad_table (login, password, user_id) VALUES ($1, $2, $3)",
                array($login, $password, $user_id));
        } catch (Exception $ex) {
            $this->logEx($ex);
        }
    }
}