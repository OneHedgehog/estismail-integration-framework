<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 10/24/17
 * Time: 11:24 AM
 */

namespace Integration\Ecwid;

use Integration\Common\Event;
use Integration\Common\Postgres;
use Integration\Common\EstisApi;


class MainAppPage extends Event
{
    protected $view = 'ecwid';
    protected $system_dir = __DIR__;

    public function __construct()
    {
        parent::__construct();
    }

    public function exec()
    {
        $local_post = $this->validateEcwidInputParams($_POST);

        $this->updateReqParams($local_post['token'], $local_post['shopId']);

        // get data from db
        $this->data['db'] = $this->getEcwidDbData($local_post['shopId']);

        $this->data['alert_data'] = array(
            '0' => array(
                'class' => 'alert-warning',
                'head' => 'Not yet connected :|',
                'mes' => 'Connect with estismail using your api-key in estismail account',
            ),
            '1' => array(
                'class' => 'alert-success',
                'head' => 'Connected :)',
                'mes' => 'Successfully connected to estismail. Start with \'Save Setting\' tab'
            ),
            '2' => array(
                'class' => 'alert-error',
                'head' => 'Connection failed :(',
                'mes' => 'Can\'t connect to estismail. Try again later or contact to support'
            )
        );

        // get data from estis
        $api_key = $this->data['db']['api_key'];

        // alert status = Connection failed
        $status = 2;
        if (preg_match("/^[a-z\d]{40}$/i", $api_key)) {
            //to collect api_key connection & list data in one method
            $this->data['estis'] = $this->getDataFromEstis($api_key);
        }

        if (!empty($this->data['estis'])) {
            $status = 1; // connected
        }

        if (empty($this->data['db']['api_key'])) {
            $status = 0; //not yet connected
        }

        //status for alert subs
        $this->data['alert_status'] = $status;

        // prepare data for ajax
        die(json_encode($this->data, ENT_QUOTES));
    }

    /**
     * @param $token
     * @param $id
     * add token to user row db
     */
    private function updateReqParams($token, $id)
    {
        $pgsql = Postgres::getInstance();
        try {
            $pgsql->query("UPDATE estis_ecwid_app SET token = $1 WHERE ecwid_id = $2", array($token, $id));
        } catch (Exception $ex) {
            $this->ajaxEx($ex);
        }
    }

    /**
     * @param $id
     * @return array or false
     */
    private function getEcwidDbData($id)
    {
        $pgsql = Postgres::getInstance();
        try {
            $data = $pgsql->query("SELECT * FROM estis_ecwid_app WHERE ecwid_id = $1 LIMIT 1", array($id));
            if (empty($data)) {
                $this->ajaxMes("SELECT * FROM estis_ecwid_app WHERE ecwid_id = " . $id . " LIMIT 1 was return an empty array()");
            }
            $data = $data[0];

            return $data;

        } catch (Exception $ex) {
            $this->ajaxEx($ex);
        }

        return false;
    }

    /**
     * @param $api_key
     * @return array user lists or false
     */
    private function getDataFromEstis($api_key)
    {
        $estis_instance = EstisApi::getInstance($api_key);
        $method = '/mailer/users';
        $response = $estis_instance->estisApiQuery($method);

        if (!empty($response)) {
            $return_arr['user'] = $response['user'];
            $method = '/mailer/lists';
            $response = $estis_instance->estisApiQuery($method);
            if (!empty($response)) {
                $return_arr['lists'] = $response['lists'];
            }
            return $return_arr;
        }

        return false;
    }

    /**
     * @param $post_data
     * @return decoded ajax $post_data
     */
    private function validateEcwidInputParams($post_data)
    {
        if (empty($post_data['post_data'])) {
            $this->ajaxMes('empty $_POST[\'post_data\']');
        }
        $post_data['post_data'] = json_decode($post_data['post_data'], 1);

        $post_data['post_data']['shopId'] *= 1;
        if (empty($post_data['post_data']['shopId'])) {
            $this->ajaxMes('empty $_POST[\'post_data\'][\'shopId\']');
        }

        if (empty($post_data['post_data']['token'])) {
            $this->ajaxMes('empty $_POST[\'post_data\'][\'token\']');
        }

        return $post_data['post_data'];
    }
}