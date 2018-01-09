<?php

namespace Integration\AmoCRM;

use Integration\Common\Event;
use Integration\Common\EstisApi;
use Integration\Common\Postgres;

use Exception;


class AmoCrmGetLists extends Event
{

	private $post_data = [];
	private $estis_err = '';

    public function __construct()
    {
        parent::__construct();
    }


    public function exec()
    {
        $this->checkInputPostParams();

        $data = $this->getUserApiKey($this->post_data['id']);

        $api_key = $data['api_key'];

        $lists = $this->getListsFromEstis($api_key);

        if($this->estis_err !== ''){
        	$this->ajaxMes($this->estis_err);
        }

        die(json_encode($lists, ENT_QUOTES));

    }

	/**
	 * @param $post_data
	 */
    public function setInputPostData($post_data){
	    $this->post_data = $post_data;
    }

    private function checkInputPostParams()
    {
        if (empty($this->post_data['id'])) {
            $this->ajaxMes('empty $post_data[\'id\']');
        }

        if (empty($this->post_data['hash_id']) && strlen($this->post_data['hash_id']) != 40) {
            $this->ajaxMes('empty or invalid $post_data[\'hash_id\']');
        }

        $crypt_id = sha1($this->post_data['id'] . '39NeSeBANo6Lqfe7lOvmL9c24NTdi0lvwOfZaHbt');
        if ($this->post_data['hash_id'] !== $crypt_id) {
            $this->ajaxMes('$post_data[\'hash_id\'] is\'nt equal to $crypt_id, $post_data[\'hash_id\'] = (' . $this->post_data['hash_id'] . ')');
        }
    }

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
    private function getUserApiKey($id)
    {
        $postgres = Postgres::getInstance();
        try {
            $data = $postgres->query('SELECT api_key FROM amocrm_table WHERE user_id=$1', array($id));
            if (!$data) {
                $this->ajaxMes('SELECT api_key FROM amocrm_table WHERE user_id=' . $id . ' was return an empty array()');
            }
            return $data[0];
        } catch (Exception $ex) {
            $this->ajaxEx($ex);
        }
    }

	/**
	 * @param $api_key
	 *
	 * @return string
	 */
    private function getListsFromEstis($api_key)
    {
        $method = '/mailer/lists';
        $estis_instance = EstisApi::getInstance($api_key);
        $response = $estis_instance->estisApiQuery($method, "GET");

        $err = $estis_instance->getErr();

	    if($err !== ''){
        	$this->estis_error = $err;
        }

        return $response;
    }
}