<?php
/**
 * Created by PhpStorm.
 * User: dima
 * Date: 10/26/17
 * Time: 1:43 PM
 */

namespace Integration\Common;

class Validator {
	static function validateEmail($email){
		if(empty($email)){
			return false;
		}
		$valid = filter_var($email, FILTER_VALIDATE_EMAIL);
		if(!$valid){
			return false;
		}


		return true;
	}



	static function validateIp($ip){

		if(empty($ip)){
			return false;
		}

		if(!filter_var($ip, FILTER_VALIDATE_IP)){
			return false;
		};

		return true;
	}


}