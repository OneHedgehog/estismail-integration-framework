<?php

namespace Integration\MoySklad;

use Integration\Common\EventSession;
use Integration\Common\Postgres;
use Exception;

class SaveEstisApiKey extends EventSession
{
    protected $system_dir = __DIR__;

    public function __construct()
    {
        parent::__construct();
    }

    public function exec()
    {
        $api_key = $this->validatePostQueryParams($_POST);

        if($api_key !== null){

            // $si - session_instance
            $si = EventSession::getInstance();
            $si->startSession();
            $user_id = $si->__get('user_id');

            if ($user_id !== null) {
                $this->saveApiKey($api_key['api_key'], $user_id);
            }
        }
        $this->redirectBack();
    }

	/**
	 * @param $post_data
	 * validate $_POST params
	 * @return null
	 */
    private function validatePostQueryParams($post_data)
    {
        if (empty($post_data['api_key']) || strlen($post_data['api_key']) > 40) {
            return null;
        }

        return $post_data;
    }


	/**
	 * @param $api_key
	 * @param $user_id
	 * update api_key in db
	 */
    private function saveApiKey($api_key, $user_id)
    {
        $psql = Postgres::getInstance();
        try {
            $psql->query("UPDATE estis_moysklad_table SET api_key = $1 WHERE user_id = $2",
                array($api_key, $user_id));
        } catch (Exception $ex) {
            $this->logEx($ex);
        }
    }
}