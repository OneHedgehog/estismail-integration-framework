<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 10/23/17
 * Time: 2:35 PM
 */

namespace Integration\Ecwid;


class EcwidApi
{
    private static $_instance = array();

    private function __construct()
    {

    }

    public static function getInstance()
    {
        if (!self::$_instance) self::$_instance = new self();
        return self::$_instance;
    }

    public function ecwidApiQuery($store_id, $method, $params = array(), $id = '')
    {
        if ($id !== '') {
            $id = '/' . $id;
        }

        $url = 'https://app.ecwid.com/api/v3/' . $store_id . '/' . $method . $id;

        if ($params !== array()) {
            $url = $url . '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}