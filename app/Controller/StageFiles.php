<?php

namespace Controller;

use Model\FileMapper;

class StageFiles extends Base
{
    public function get()
    {
        $user = $this->getLoggedUser();
        if ($user == null)
            return;

        $fileMapper = new FileMapper($this->getApplication()->getDbConnection());
        $files = $fileMapper->getStageFiles($user->id);

        echo $this->render('stage_files.twig', array(
            'stage_files' => $files
        ));
    }
}