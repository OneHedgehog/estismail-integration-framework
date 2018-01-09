<?php

namespace Integration\Common;


class EventSession extends Event
{
    const SESSION_STARTED = true;
    const SESSION_NOT_STARTED = false;

    // The state of the session
    private $session_state = self::SESSION_NOT_STARTED;

    private static $_instance;

    public function __construct()
    {
        parent::__construct();
    }

    public function exec() {

    }

    public static function getInstance()
    {
        if (!self::$_instance) self::$_instance = new self();
        self::$_instance->startSession();

        return self::$_instance;
    }

    public function startSession()
    {
        if ($this->session_state == self::SESSION_NOT_STARTED) {
            $this->session_state = session_start();
        }

        return $this->session_state;
    }

    public function __set($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    public function __get($name)
    {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }
    }

    public function __isset($name)
    {
        return isset($_SESSION[$name]);
    }

    public function __unset($name)
    {
        unset($_SESSION[$name]);
    }

    public function destroy()
    {
        if ($this->session_state == self::SESSION_STARTED) {
            $this->session_state = !session_destroy();
            unset($_SESSION);

            return !$this->session_state;
        }
        return false;
    }
}