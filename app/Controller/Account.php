<?php

namespace Controller;

use Model\UserMapper;

class Account extends Base
{
    public function get()
    {
        $user = $this->getLoggedUser();
        if ($user == null)
        {
            header('Location: /login');
            return;
        }

        echo $this->render('account.twig', array(
            'page_title' => 'Hesabım',
            'current_page' => 'account'
        ));
    }

    public function post()
    {
        $user = $this->getLoggedUser();
        if ($user == null)
        {
            header('Location: /login');
            return;
        }

        // Parametreler alınıyor.
        $email = isset($_POST['email']) ? $_POST['email'] : null;
        $password = isset($_POST['password']) ? $_POST['password'] : null;
        $passwordRepeat = isset($_POST['password_repeat']) ? $_POST['password_repeat'] : null;


        if ($password != null)
        {
            if (strlen($password) < 6)
            {
                self::$viewContainer['message_type'] = 'alert-error';
                self::$viewContainer['message'] = 'Şifre en az 6 karakter olmalı.';
                $this->get();
                return;
            }

            if ($password != $passwordRepeat)
            {
                self::$viewContainer['message_type'] = 'alert-error';
                self::$viewContainer['message'] = 'Şifre ile şifre tekrarı alanları aynı içeriğe sahip olmalı.';
                $this->get();
                return;
            }
        }

        if ($email == null)
        {
            self::$viewContainer['message_type'] = 'alert-error';
            self::$viewContainer['message'] = 'E-posta adresiniz gerekli.';
            $this->get();
            return;
        }

        $userMapper = new UserMapper($this->getApplication()->getDbConnection());

        if ($email != null && $this->getLoggedUser()->email != $email)
        {
            if ($userMapper->checkEmail($email))
            {
                self::$viewContainer['message_type'] = 'alert-error';
                self::$viewContainer['message'] = 'Seçtiğiniz e-posta adresi kullanımda.';
                $this->get();
                return;
            }
        }

        $this->getLoggedUser()->email = $email;

        $userMapper->saveUser($this->getLoggedUser()->id, $email, $password, $passwordRepeat);

        self::$viewContainer['message_type'] = 'alert-success';
        self::$viewContainer['message'] = 'Bilgileriniz kaydedildi.';
        $this->get();
    }
}
