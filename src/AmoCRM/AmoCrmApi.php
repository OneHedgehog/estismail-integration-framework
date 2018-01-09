<?php

namespace Integration\AmoCRM;

use Exception;

class AmoCrmApi
{
    private static $_instance;
    private $email = '';
    private $api_key = '';
    private $subdomain = '';
    private $methods = array(
        'auth' => 'auth.php?type=json',
        'account' => 'v2/json/accounts/current',
        'contacts' => 'v2/json/contacts/list'
    );

    private function __construct($email, $api_key, $subdomain)
    {
        $this->email = $email;
        $this->api_key = $api_key;
        $this->subdomain = $subdomain;
    }

    public static function getInstance($email, $api_key, $subdomain)
    {
        if (!self::$_instance) self::$_instance = new self($email, $api_key, $subdomain);
        return self::$_instance;
    }

    /**
     * Make query to amoCRM API
     * @link https://developers.amocrm.ru/rest_api
     * @param string $method
     * Methods of requesting API:
     * To access the system data, both through interfaces and through the API, authorization under the
     * user account is REQUIRED. All methods can be used ONLY after authorization.
     * << $type - POST query to $method - (auth) >>
     * @param string $type POST/GET
     * @return false or string of response
     */
    public function amoCrmQuery($method, $type = "POST")//, $params = array()
    {
        $resourceUrl = 'https://' . $this->subdomain . '.amocrm.ru/private/api/' . $method;

//        if (!$this->methodChecker($method)) {
//            return false;
//        }

        $user = array(
            'USER_LOGIN' => $this->email, // Your Login (email)
            'USER_HASH' => $this->api_key // Api Key in settings
        );

        $ch = curl_init();

        switch ($type) {
            case("POST"): {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($user));
            }
            case("GET"): {
                $resourceUrl = 'https://' . $this->subdomain . '.amocrm.ru/private/api/' . $method . '?' . http_build_query($user);
            }
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
        curl_setopt($ch, CURLOPT_URL, $resourceUrl);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__) . '/cookie.txt');
        curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__) . '/cookie.txt');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->checkCurlResponse($code);
        return $response;
    }

    private function methodChecker($method)
    {
        foreach ($this->methods as $link_method) {
            if ($link_method === $method) {
                return true;
            }
        }
        return false;
    }

    private function checkCurlResponse($code)
    {
        $code*=1;
        $errors = array(
            301 => 'Moved permanently',
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable'
        );
        try {
            if ($code != 200 && $code != 204)
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error', $code);
        } catch ( Exception $ex ){
            die('Error: ' . $ex->getMessage() . PHP_EOL . '<br>' . 'Code error: ' . $ex->getCode());
        }
    }
}