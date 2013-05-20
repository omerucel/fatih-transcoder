<?php

namespace Controller;

use Model\SessionMapper;

class Logout extends Base
{
    public function get()
    {
        if (isset($_COOKIE['token']))
        {
            $sessionMapper = new SessionMapper($this->getApplication()->getDbConnection());
            $sessionMapper->deleteToken($_COOKIE['token']);
            setcookie('token', null, -1);
        }

        header('Location: /');
    }
}