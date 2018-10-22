<?php

namespace Integration\Common;

use Exception;

class Postgres extends Config
{
    private $selected_data = array();

    protected $_connection = array();

    private static $_instance;

    private function __construct()
    {
        $system_url = $_GET['system'];
        $this->_connection = pg_connect(
            "host=" . $this->postgres[$system_url]['host'] .
            " dbname=" . $this->postgres[$system_url]['db'] .
            " user=" . $this->postgres[$system_url]['user'] .
            " password=" . $this->postgres[$system_url]['pas'] . "");

    }

    public static function getInstance()
    {
        if (!self::$_instance) self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * @param $query
     * @param array $data
     *
     * @return bool
     * @throws Exception
     */
    public function query($query, $data = array())
    {
        $ret = pg_query_params($this->_connection, $query, $data);

	    $error = pg_last_error($this->_connection);
	    if (!empty($error)) {
		    throw new Exception($error);
	    }


        while ($row = pg_fetch_assoc($ret)) {
            array_push($this->selected_data, $row);
        }
        return $this->selected_data;
    }

    // Magic method clone is empty to prevent duplication of connection
    private function __clone()
    {

    }

    public function getConnection()
    {
        //return $this->$_connection;
    }
}
