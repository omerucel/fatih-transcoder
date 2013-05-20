<?php

namespace Controller;

use Model\FileMapper;

class FileManager extends Base
{
    public function get()
    {
        $user = $this->getLoggedUser();
        if ($user == null)
        {
            header('Location: /login');
            return;
        }

        echo $this->render('file_manager.twig', array(
            'page_title' => 'Dosya Yöneticisi',
            'current_page' => 'file-manager'
        ));
    }
}