<?php

namespace Integration\MoySklad;

use Integration\Common\EventSession;
use Integration\Common\Postgres;
use Integration\Common\EstisApi;
use Integration\MoySklad\MoySkladApi;
use Integration\Common\Validator;
use Exception;

class SubscribeList extends EventSession
{
    protected $system_dir = __DIR__;

    public function __construct()
    {
        parent::__construct();
    }

    public function exec()
    {
        $post_params = $this->validatePostQueryParams($_POST);

        //list length can be less, then one
        if ($post_params !== null) {

            // $si - session_instance
            $si = EventSession::getInstance();
            $si->startSession();


            $user_id = $si->__get('user_id');

            if ($user_id !== null) {
                $login_data = $this->getUserData($user_id);

                $response = $this->getDataFromEstis($login_data['api_key']);
                $lists = $response['lists'];

                foreach ($lists as $key => $value) {
                    $lists[$value['id']] = $value;
                    unset($lists[$key]);
                }

                if (!isset($lists[$post_params['list']])) {
                    $this->redirectBack();
                }

                if (!empty($login_data['webhook_id'])) {
                   $this->updateMoyskladWebhook($login_data,false);
                };

                $this->iterateUsersFromList($login_data['api_key'], $post_params['list'], $login_data);

                $this->updateMoyskladWebhook($login_data,true);


            }
        }
        $this->redirectBack();
    }

	/**
	 * @param $post_data
	 * validate post_params
	 * @return null
	 */
    private function validatePostQueryParams($post_data)
    {
    	//DON'T DIE. Maybe, we don't have lists
        if (empty($post_data)) {
            return null;
        }
        return $post_data;
    }


	/**
	 * @param $user_id
	 * get all data from db
	 * @return mixed ( array from db )
	 */
    private function getUserData($user_id)
    {
        $psql = Postgres::getInstance();
        try {
            $data = $psql->query("SELECT * FROM estis_moysklad_table WHERE user_id = $1 LIMIT 1", array($user_id))[0];
            if ($data === null) {
                $this->logMes('SELECT * FROM estis_moysklad_table WHERE user_id =' . $user_id . ' LIMIT 1 was return empty array()');
            }
        } catch (Exception $ex) {
            $this->logEx($ex);
        }
        return $data;
    }


	/**
	 * @param $api_key
	 * get lists from Estis
	 * @return string ( JSON ) | bool false if fail
	 */
    private function getDataFromEstis($api_key)
    {
        $method = '/mailer/lists';
        $estis_instance = EstisApi::getInstance($api_key);
        $response = $estis_instance->estisApiQuery($method, "GET");

        return $response;
    }

	/**
	 * @param $login_params
	 * @param $activate
	 * true -> activate webhook
	 * false -> deactivate webhook
	 * activate/deactivate webhook
	 * @return mixed
	 */
    private function updateMoyskladWebhook($login_params, $activate)
    {
        $moysklad = MoySkladApi::getInstance($login_params['login'], $login_params['password']);
        $url = 'entity/webhook/' . $login_params['webhook_id'];
        $params = array('enabled' => $activate);
        $res = $moysklad->moySkladQuery( $url, $params, 'PUT');
        echo($res);
        return json_decode($res, 1);
    }

	/**
	 * @param $api_key
	 * @param $list_id
	 * @param $login_data
	 * @param int $i
	 * loop to push users from estis to Moysklad
	 * @return bool
	 */
    private function iterateUsersFromList($api_key, $list_id, $login_data, $i = 1)
    {
        $estis_instance = EstisApi::getInstance($api_key);
        $method = '/mailer/emails';

        while (true) {

            $params = array(
                'fields' => json_encode(array('email', 'id', 'name', 'city', 'phone')),
                'list_id' => $list_id,
                'limit' => 100,
                'page' => $i
            );

            $response = $estis_instance->estisApiQuery($method, "GET", $params);
            $subscribers = $response['emails'];

            if (empty($subscribers)) {
                return false;
            }

            foreach ($subscribers as $user) {

                if (!empty($user['email']) && filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {

                    if (empty($user['name'])) {
                        $user['name'] = '';
                    }

                    if (empty($user['phone'])) {
                        $user['phone'] = '';
                    }

                    if (empty($user['city'])) {
                        $user['city'] = '';
                    }

                    $this->exportToMoysklad($login_data, $user);
                }
            }
            $i += 1;
        }
    }

	/**
	 * @param $login_params
	 * push ONE_ESTIS_USER to Moysklad
	 * @param $user_params
	 */
    private function exportToMoysklad($login_params, $user_params)
    {

    	if($user_params['email']){
    		Validator::validateEmail($user_params['email']);
	    }

        $params = array(
            'email' => $user_params['email'],
            'name' => htmlentities($user_params['name']),
            'phone' => htmlentities($user_params['phone']),
            'actualAddress' => htmlentities($user_params['city'])
        );

        $moysklad_instance = MoySkladApi::getInstance($login_params['login'], $login_params['password']);
        $method = 'entity/counterparty';
        $moysklad_instance->moySkladQuery( $method, $params, "POST");
    }
}