<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 9/25/17
 * Time: 12:44 PM
 */

namespace Integration\Common;


class EstisApi {

	private $url = 'https://v1.estismail.com';

	private $api_key = '';

	private $err_code = 0;
	private $method_valid = true; //check if we have valid method
	private $type = 'GET';
	private $res = ''; //keep response there

	private $methods = array(
		'/mailer/'=>
			array(
				'emails',
				'forms',
				'lists',
				'makets',
				'senderemails',
				'sends',
				'sendstatistics',
				'users'
            )
    );

    public static $_instance;

    public function __construct($api_key) {
        self::$_instance = $this;
        $this->api_key = $api_key;
    }

    public static function getInstance($api_key) {
        if (self::$_instance === null) {
            self::$_instance = new self($api_key);
        }
        return self::$_instance;
    }


    /**
     * @param string $api_method
     * @param string $type
     * @param array $params
     * @internal array $params
     * @return string ($response)
     */
    public function estisApiQuery( $api_method, $type="GET", $params = array()) {

    	$this->type = $type;
        $resourceUrl = $this->url . $api_method;

        if(!$this->methodChecker($api_method)){
        	print_r($api_method);
        	return false;
        }

	    if(!$this->cleanApiKey($this->api_key)){
		    return false;
	    }


        if($type === "GET" && !empty($params)){
            $resourceUrl = $resourceUrl . '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $resourceUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-Estis-Auth: '.$this->api_key
        ));

        switch($type){
            case("POST"):{
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            } break;
        }

        $response = json_decode(curl_exec($ch),1);
        $this->res = $response;
	    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	    if(!$this->resCodeChecker($code)){
	    	//echo 'Http code' . $code;
	    	return false;
	    }
        curl_close($ch);
	    if(empty($response)){
	    	return false;
	    }
        return $response;
    }


    private function cleanApiKey($api_key){
	    if(!preg_match("/^[a-z\d]{40}$/i", $api_key)){
	    	$this->err_code = 4000;
		    return false;
	    }
	    return $api_key;
    }

    private function methodChecker($method){
	    $this->estis_method = $method;
		foreach ($this->methods['/mailer/'] as $key=>$value){
			if('/mailer/' . $value === $method){
				return true;
			};
		}
		$this->method_valid = false;
		//echo('false method');
		return false;
    }






    private function resCodeChecker($code){
    	switch ($code){
		    case(200):{
		    	return true;
		    } break;
		    case(201):{
		    	return true;
		    } break;
		    case(204):{
		    	return true;
		    }
	    }

	    $this->err_code = $code;
	    return false;
    }


    public function getErr(){
		$code = $this->err_code;
		$method = $this->methods;

		$method_valid = $this->method_valid;

		$mes = '';
		if($method_valid === false){
			$mes = 'Invalid estismail api method ' . $method;
			return  $mes;
		}

		if(!empty($res['name']) && !empty($code)){
			$mes = $res['name'];
			return $mes;
		};

	    if(!empty($code) && $code === 4000){
		    $mes = 'Invalid api key';
		    return $mes;
	    }


		if(!empty($code)){
			$mes = $this->res['name'];
			return $mes;
		}

		return $mes;

    }
}