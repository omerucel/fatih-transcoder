<?php

namespace Controller;

use Model\JobMapper;

class History extends Base
{
    public function get()
    {
        $user = $this->getLoggedUser();
        if ($user == null)
        {
            header('Location: /login');
            return;
        }

        $is_complete = isset($_GET['is_complete']) ? $_GET['is_complete'] : 0;

        $jobMapper = new JobMapper($this->getApplication()->getDbConnection());
        if ($is_complete == 1)
        {
            $jobs = $jobMapper->getHistory($user->id, $is_complete);
        }else{
            $jobs = null;
        }

        echo $this->render('history.twig', array(
            'current_page' => 'history',
            'page_title' => 'İşlem Geçmişi',
            'jobs' => $jobs,
            'is_complete' => $is_complete
        ));
    }
}