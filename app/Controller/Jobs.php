<?php

namespace Controller;

use Model\JobMapper;

class Jobs extends Base
{
    public function get()
    {
        $user = $this->getLoggedUser();
        if ($user == null)
        {
            header('HTTP/1.1 401');
            return;
        }

        $jobMapper = new JobMapper($this->getApplication()->getDbConnection());
        $jobs = $jobMapper->getJobs($user->id);

        echo $this->render('jobs.twig', array(
            'jobs' => $jobs
        ));
    }
}