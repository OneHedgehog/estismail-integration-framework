<?php

namespace Integration\MoySklad;

use Integration\Common\EstisApi;
use Integration\Common\EventSession;
use Integration\Common\Postgres;
use Integration\Common\Validator;
use Integration\MoySklad\MoySkladApi;

use Exception;

class SaveEstisSettings extends EventSession
{
    public function __construct()
    {
        parent::__construct();
    }

    public function exec()
    {
        $post_params = $this->validatePostQueryParams($_POST);

        if ($post_params !== null) {

            // $si - session_instance
            $si = EventSession::getInstance();
            $si->startSession();
            $user_id = $si->__get('user_id');

            if ($user_id !== null) {
                $data_array = $this->selectDbData($user_id);

                $response = $this->getDataFromEstis($data_array['api_key']);
                $lists = $response['lists'];

                foreach ($lists as $key => $value) {
                    $lists[$value['id']] = $value;
                    unset($lists[$key]);
                }

                if (!isset($lists[$post_params['list']])) {
                    $post_params['list'] = null;
                }

                $webhook_id = $data_array['webhook_id'];

                // check current active webhook
                if (empty($webhook_id)) {
                    $current_webhook = $this->getWebhook($data_array);
                    $webhook_id = $current_webhook['rows'][0]['id'];

                    // register new webhook
                    if (empty($webhook_id)) {
                        $hook_data = $this->registerWebhook($data_array, $user_id);
                        $webhook_id = $hook_data['id'];
                    }
                }

                // activate/deactivate webhook
                if (isset($post_params['webhook_opt_in']) == 1) {
                    $this->updateMoyskladWebhook($data_array, $webhook_id, false);
                } else {
                    $this->updateMoyskladWebhook($data_array, $webhook_id, true);
                }

                $this->saveEstisParams($post_params, $webhook_id, $user_id);
            }
        }
        $this->redirectBack();
    }

    /**
     * @param $post_data
     * checking post params
     * @return mixed
     */
    private function validatePostQueryParams($post_data)
    {
        if (!empty($post_data['double_opt_in']) && $post_data['double_opt_in'] != 1) {
            $this->logMes('Invalid $_POST[\'double_opt_in\'] value =  ' . json_encode($post_data['double_opt_in']));
        }

        if (!empty($post_data['webhook_opt_in']) && $post_data['webhook_opt_in'] != 1) {
            $this->logMes('Invalid $_POST[\'webhook_opt_in\'] value =  ' . json_encode($post_data['webhook_opt_in']));
        }

        return $post_data;
    }

    /**
     * @param $post_data
     * @param $webhook_id
     * @param $user_id
     * Save estis params to db
     */
    private function saveEstisParams($post_data, $webhook_id, $user_id)
    {
        $post = Postgres::getInstance();
        try {
            $post->query("UPDATE estis_moysklad_table SET list_id = $1, double_opt_in = $2, webhook_id = $4, webhook_opt_in = $5 WHERE user_id = $3",
                array($post_data['list'], $post_data['double_opt_in'], $user_id, $webhook_id, $post_data['webhook_opt_in']));
        } catch (Exception $ex) {
            $this->logEx($ex);
        }
    }

    /**
     * @param $id
     * get all required data from db
     * @return mixed ( array from db )
     */
    private function selectDbData($id)
    {
        $psql = Postgres::getInstance();
        try {
            $data = $psql->query("SELECT login, password, api_key, webhook_id FROM estis_moysklad_table WHERE user_id = $1 LIMIT 1",
                array($id))[0];
            if ($data === null) {
                $this->logMes("SELECT login, password, api_key, webhook_id FROM estis_moysklad_table WHERE user_id = " . $id . "was return an empty array()");
            }
            return $data;
        } catch (Exception $ex) {
            $this->logEx($ex);
        }
    }

    /**
     * @param $api_key
     * get lists from estismail
     * @return string
     */
    private function getDataFromEstis($api_key)
    {
        $method = '/mailer/lists';
        $estis_instance = EstisApi::getInstance($api_key);
        $response = $estis_instance->estisApiQuery($method, "GET");

        return $response;
    }

    /**
     * @param $user_data
     * check if we have webhook, get id
     * @return mixed
     */
    private function getWebhook($user_data)
    {
        $sklad = MoySkladApi::getInstance($user_data['login'], $user_data['password']);
        $hook_data = $sklad->moySkladQuery('/entity/webhook', $params = array(), 'GET');
        return json_decode($hook_data, true);
    }

    /**
     * @param $user_data
     * @param $user_id
     * register new webhook ( notice! we can do that action only one time )
     * @return mixed
     */
    private function registerWebhook($user_data, $user_id)
    {
        $sklad = MoySkladApi::getInstance($user_data['login'], $user_data['password']);
        $params = array(
            'url' => ESTIS_HOST_NAME . '/integration/moysklad/webhook?id=' . $user_id . '&hash=' . sha1('M0iCJGePNKmY4TKFWof0qdNaG7u20rQmlyDiQNMn' . $user_id),
            'action' => 'CREATE',
            'entityType' => 'counterparty'
        );
        $hook_data = $sklad->moySkladQuery('/entity/webhook', $params, 'POST');
        return json_decode($hook_data, true);
    }

    private function updateMoyskladWebhook($user_data, $webhook_id, $activate)
    {
        $moysklad = MoySkladApi::getInstance($user_data['login'], $user_data['password']);
        $url = 'entity/webhook/' . $webhook_id;
        $params = array('enabled' => $activate);
        $response = $moysklad->moySkladQuery($url, $params, 'PUT');
        return json_decode($response, 1);
    }
}