<?php

namespace Controller;

use Model\SessionMapper;
use Model\CategoryMapper;

abstract class Base
{
    /**
     * @var \Application
     */
    protected static $application;

    /**
     * @var array
     */
    public static $viewContainer = array();

    public static $loggedUser = null;

    /**
     * @param \Application $application
     */
    public static function setApplication(\Application $application)
	{
		self::$application = $application;
	}

    /**
     * @return \Application
     */
    public function getApplication()
	{
		return self::$application;
	}

    public function render($path, $params = array())
    {
        $viewConfigs = $this->getApplication()->getConfig('views');
        self::$viewContainer['base_url'] = $viewConfigs['base_url'];
        self::$viewContainer['user'] = $this->getLoggedUser();
        self::$viewContainer['token'] = $this->getToken();
        $params = array_merge_recursive(self::$viewContainer, $params);
        return $this->getApplication()->getTwigEnvironment()->render($path, $params);
    }

    public function hasToken()
    {
        return isset($_COOKIE['token']) || isset($_GET['token']) || isset($_POST['token']);
    }

    public function getToken()
    {
        $token = isset($_POST['token']) ? $_POST['token'] : "";
        if ($token != '')
            return $token;

        $token = isset($_GET['token']) ? $_GET['token'] : "";
        if ($token != '')
            return $token;

        $token = isset($_COOKIE['token']) ? $_COOKIE['token'] : "";
        if ($token != '')
            return $token;
    }

    public function getLoggedUser()
    {
        if (!$this->hasToken()) return null;

        if (self::$loggedUser == null)
        {
            $sessionMapper = new SessionMapper($this->getApplication()->getDbConnection());
            self::$loggedUser = $sessionMapper->getUser($this->getToken());
        }

        return self::$loggedUser;
    }

    public function showForbidden()
    {
        echo $this->getApplication()->getTwigEnvironment()->render('403.twig');
    }
}
