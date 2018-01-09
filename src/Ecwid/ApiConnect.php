<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 10/5/17
 * Time: 2:46 PM
 */

namespace Integration\Ecwid;

use Integration\Common\Event;
use Integration\Common\EstisApi;
use Integration\Common\Postgres;

use Exception;

class ApiConnect extends Event
{
    public function __construct()
    {
        parent::__construct();
    }

    public function exec()
    {
        $local_post = $this->checkInputPostQueryData($_POST);
        $this->updateApiKeyEcwidDb($local_post['api_key'], $local_post['id']);

        // success update in db
        die(json_encode(array('data' => '1'), ENT_QUOTES));
    }

    /**
     * validate $_POST input params
     * if not valid, die()
     * @param $post_data
     * @return array()
     */
    private function checkInputPostQueryData($post_data)
    {
        if (empty($post_data['post_data'])) {
            $this->ajaxMes('empty $_POST[\'post_data\']');
        }

        //if invalid post_data
        try{
	        $post_data['post_data'] = json_decode($post_data['post_data'], 1);
        } catch ( Exception $ex) {
			$this->ajaxEx($ex);
        }


        $post_data['post_data']['id'] *= 1;
        if (empty($post_data['post_data']['id'])) {
            $this->ajaxMes('Empty ecwid $_POST[\'post_data\'][\'shop_id\'] ');
        }

        //redirect if empty or too big api_key
        if (empty($post_data['post_data']['api_key']) || strlen($post_data['post_data']['api_key']) > 40) {
            die(json_encode(['data' => '0'], ENT_QUOTES));
        }
        return $post_data['post_data'];
    }

    /**
     * @param $api_key
     * @param $id
     * add a api_key to user row db
     */
    private function updateApiKeyEcwidDb($api_key, $id)
    {
        $pgsql = Postgres::getInstance();
        try {
            $pgsql->query("UPDATE estis_ecwid_app SET api_key = $1 WHERE id = $2", array($api_key, $id));
        } catch (Exception $ex) {
            $this->ajaxEx($ex);
        }
    }

}