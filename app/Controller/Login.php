<?php

namespace Controller;

use Model\UserMapper;
use Model\SessionMapper;

class Login extends Base
{
    public function get()
    {
        echo $this->render('login.twig', array(
            'current_page' => 'login',
            'page_title' => 'Oturum Aç'
        ));
    }

    public function post()
    {
        $email = isset($_POST['email']) ? $_POST['email'] : null;
        $password = isset($_POST['password']) ? $_POST['password'] : null;

        // Kullanıcı kaydını gerçekleştir.
        $userMapper = new UserMapper($this->getApplication()->getDbConnection());
        $userId = $userMapper->checkUser($email, $password);

        if ($userId == null)
        {
            self::$viewContainer['message'] = 'E-posta adresi ya da şifre hatalı';
            $this->get();
            return;
        }

        // Oturum açılıyor.
        $sessionMapper = new SessionMapper($this->getApplication()->getDbConnection());
        $token = $sessionMapper->createSession($userId);

        setcookie('token', $token);

        header('Location: /file-manager');
    }
}
