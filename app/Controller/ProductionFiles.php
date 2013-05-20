<?php

namespace Controller;

use Model\FileMapper;

class ProductionFiles extends Base
{
    public function get()
    {
        $user = $this->getLoggedUser();
        if ($user == null)
            return;

        $fileMapper = new FileMapper($this->getApplication()->getDbConnection());
        $files = $fileMapper->getProductionFiles($user->id);

        echo $this->render('production_files.twig', array(
            'production_files' => $files
        ));
    }
}