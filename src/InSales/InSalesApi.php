<?php

namespace Integration\InSales;


class InSalesApi {

	private static $_instance = array();

	private function __construct() {

	}

	public static function getInstance() {
		if (!self::$_instance) self::$_instance = new self();
		return self::$_instance;
	}


    public function inSalesApiQuery($password, $method, $shop, $type="GET", $params=array()){
    	$app_name = 'estisApp';
        $url = 'http://' . $app_name . ':' . $password  . '@'. $shop  . "/". $method;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	    $header = array();
	    $header[] = 'Content-Type: application/json';
	    $header[] = 'Authorization: Basic ' . base64_encode($app_name . ':' . $password);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        switch($type){
            case("GET"):
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET' );
                $url = $url . '?' . http_build_query($params);
                break;

            case("POST"):
                curl_setopt($ch, CURLOPT_POST, true);;
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,  ENT_QUOTES));
                break;

            case("DELETE"):
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE' );
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                break;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}