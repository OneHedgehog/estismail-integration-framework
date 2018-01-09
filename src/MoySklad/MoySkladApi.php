<?php

namespace Integration\MoySklad;

class MoySkladApi
{
    private static $_instance;
    private $url = 'https://online.moysklad.ru/api/remap/1.1/';
    private $method = 'entity/counterparty';    // until one method

	private $login = '';
	private $password = '';

    private function __construct($login, $password)
    {
		$this->login = $login;
		$this->password = $password;
    }

    public static function getInstance($login, $password)
    {
        if (!self::$_instance) self::$_instance = new self($login, $password);
        return self::$_instance;
    }

    /**
     * @param string $type
     * @param array $params
     * @return array
     * @internal param $method
     */
    public function moySkladQuery( $method, $params = array(), $type = "GET")
    {

    	$resourceUrl = $this->url . $method;

    	$full_url_check = stripos($method,$this->url ); //care return false || index in array. If we don't use '===', we have an error

    	if($full_url_check !== false){
		    $resourceUrl = $method;
	    }


        if($type === "GET" && !empty($params)){
            $resourceUrl = $resourceUrl . '?' . http_build_query($params);
        }


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $resourceUrl );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$this->login:$this->password");


        switch($type){
            case("POST"):{
	            $header[] = 'Content-Type: application/json';
	            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	            $params = json_encode($params, ENT_QUOTES);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            } break;
	        case("DELETE"):{
		        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE' );
		        break;
	        }
	        case("PUT"):{
		        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT' );
		        $header[] = 'Content-Type: application/json';
		        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	        	$params = json_encode($params, ENT_QUOTES);
		        curl_setopt($ch, CURLOPT_HEADER, false);
		        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	        }
        }

        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}