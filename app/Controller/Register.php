<?php

namespace Controller;

use Model\UserMapper;
use Model\SessionMapper;

class Register extends Base
{
    public function get()
    {
        echo $this->render('register.twig', array(
            'page_title' => 'Kayıt Ol',
            'current_page' => 'register'
        ));
    }

    public function post()
    {
        $email = isset($_POST['email']) ? $_POST['email'] : null;
        $password = isset($_POST['password']) ? $_POST['password'] : null;
        $passwordRepeat = isset($_POST['password_repeat']) ? $_POST['password_repeat'] : null;

        if ($password == null)
        {
            self::$viewContainer['message'] = 'Şifre gerekli.';
            $this->get();
            return;
        }

        if (strlen($password) < 6)
        {
            self::$viewContainer['message'] = 'Şifre en az 6 karakter olmalı.';
            $this->get();
            return;
        }

        if ($password != $passwordRepeat)
        {
            self::$viewContainer['message'] = 'Şifre ile şifre tekrarı alanları aynı içeriğe sahip olmalı.';
            $this->get();
            return;
        }

        if ($email == null)
        {
            self::$viewContainer['message'] = 'E-posta adresiniz gerekli.';
            $this->get();
            return;
        }

        $userMapper = new UserMapper($this->getApplication()->getDbConnection());
        if ($email != null)
        {
            if ($userMapper->checkEmail($email))
            {
                self::$viewContainer['message'] = 'Seçtiğiniz e-posta adresi kullanımda.';
                $this->get();
                return;
            }
        }

        // Kullanıcı kaydını gerçekleştir.
        $userId = $userMapper->createUser($email, $password);

        // Oturum açılıyor.
        $sessionMapper = new SessionMapper($this->getApplication()->getDbConnection());
        $token = $sessionMapper->createSession($userId);

        setcookie('token', $token);

        echo $this->render('welcome.twig', array(
            'page_title' => 'Hoşgeldiniz'
        ));
    }
}
