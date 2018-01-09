<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 9/26/17
 * Time: 1:49 PM
 */

namespace Integration\InSales;

use Integration\Common\Postgres;
use Integration\Common\EstisApi;
use Integration\InSales\InSalesApi;
use Integration\Common\Event;
use Integration\Common\Validator;

use Exception;

class SubscribeAll extends Event
{
    protected $system_dir = __DIR__;

    //array of subscriber's and subscription errors, which we will show in view
    private $subs_counter = array(
        'success' => 0,
        'err' => 0
    );

    //put here required data in exec() method ( for insales api queries )
    private $db_data = array();

    public function __construct()
    {
        parent::__construct();
    }

    public function exec()
    {
        $_POST = $this->validatePostQueryParams($_POST);

        //select from db all req data. If can't - die
        $this->db_data = $this->getInsalesReqDbData($_POST['id']);

        //check estis connection
        $success_connection = $this->checkEstisConnection($this->db_data['api_key']);

        //if we have not user from estis
        if ($success_connection === false) {
            $this->redirectBack();
        }

        //subscribe method return self | false
        $this->subscribeAllCustomers();

        //save $subs_counter to db. If can't, die
        $this->saveSubscriptionArray();

	    $this->redirectBack();
    }

    /**
     * check $_POST input, if !valid die()
     * @param array() $post_data
     * @return array $post_data;
     */
    private function validatePostQueryParams($post_data)
    {
        // our db id
        $post_data['id'] *= 1;

        if (empty($post_data['id'])) {
            $this->logMes('$_POST[\'id\'] are empty or not int, $_POST[\'id\'] = (' . $post_data['id'] . ')');
        }

        if (empty($post_data['hash_id'])) {
            $this->logMes('empty $_POST[\'hash_id\']');
        }

        if ($post_data['hash_id'] !== sha1('hp9Ur8Rvw0tTTC7WbklFkUKYqKzH7bxL072V8Wru' . $post_data['id'])) {
            $this->logMes('Invalid hash id, ' . $post_data['hash_id'] . '!==' . sha1('hp9Ur8Rvw0tTTC7WbklFkUKYqKzH7bxL072V8Wru' . $post_data['id']));
        }

        return $post_data;
    }

    /**
     * @param int $id get all data form db, if can't get, die()
     * @return bool
     */
    private function getInsalesReqDbData($id)
    {
        $pgsql = Postgres::getInstance();
        try {
            $data = $pgsql->query("SELECT hash, shop, list_id, double_opt_in, api_key FROM in_sales WHERE id = $1 LIMIT 1", array($id));
            if (!$data) {
                $this->logMes("SELECT hash, shop, list_id, double_opt_in, api_key FROM in_sales WHERE id = " . $id . " LIMIT 1 return empty post");
            }
        } catch (Exception $ex) {
            $this->logEx($ex);
        }

        $data = $data[0];

        if ((!$data['shop'])) {
            $this->logMes('empty db[\'shop\']: ' . $data['shop']);
        }

        return $data;
    }

    /**
     * @param $api_key
     * @return bool check if estis connected
     */
    private function checkEstisConnection($api_key)
    {
        $estis_instance = EstisApi::getInstance($api_key);
        $response = $estis_instance->estisApiQuery('/mailer/users');

        if (!$response) {
            return false;
        }
        return true;
    }

    /**
     * @return bool
     * @internal param int $i recursive counter for subscribe all method
     */
    private function subscribeAllCustomers()
    {
        $insales_instance = InSalesApi::getInstance();

        //Insales func params
        $password = $this->db_data['hash'];
        $shop = $this->db_data['shop'];
        $method = 'admin/orders.json';

        //InSales API params
        $get_params = array(
            'per_page' => 2, //subscribers, per page ( InSales get params )
            'page' => 1 //number of selected orders pack
        );

        $estis_instance = EstisApi::getInstance($this->db_data['api_key']);

        while (true) {

            //Connect to Insales
            $response = $insales_instance->inSalesApiQuery($password, $method, $shop, "GET", $get_params);

            $double_opt_in = 0;
            if (empty($this->db_data['double_opt_in'])) {
                $double_opt_in = 1;
            }

            $one_user_body = array(
                'email' => '',
                'list_id' => $this->db_data['list_id'],
                'activation_letter' => $double_opt_in
            );

            $clients = json_decode($response, true);

            if (empty($clients)) {
                return false;
            }

            //loop for subscription pack ( 25 subscribers by default )
            foreach ($clients as $value) {

                //if user not confirm subscription
                if (empty($value['client']['subscribe'])) {
                    continue;
                }

                if (Validator::validateEmail($value['client']['email']) === false) {
                    $this->subs_counter['err'] = $this->subs_counter['err'] + 1;
                    continue;
                }

                $one_user = $one_user_body;

                $one_user['email'] = $value['client']['email'];
                $one_user['name'] = htmlentities($value['client']['name']);
                $one_user['phone'] = htmlentities($value['client']['phone']);

                if (Validator::validateIp($value['client']['ip_addr']) === true) {
                    $one_user['ip'] = $value['client']['ip_addr'];
                }

                $subscribe = $estis_instance->estisApiQuery('/mailer/emails', "POST", $one_user);

                //statistic of success/error in subscription
                if ($subscribe) {
                    $this->subs_counter['success'] = $this->subs_counter['success'] + 1;
                } else {
                    $this->subs_counter['err'] = $this->subs_counter['err'] + 1;
                }
            }
            $get_params['page']++;
        }
    }

    /**
     * make statistic of err/fail subscription
     */
    private function saveSubscriptionArray()
    {
        $data = json_encode($this->subs_counter, ENT_QUOTES);
        $pgsql = Postgres::getInstance();
        try {
            $pgsql->query("UPDATE in_sales SET subscribe_all = $1", array($data));
        } catch (Exception $ex) {
            $this->logEx($ex);
        }
    }
}