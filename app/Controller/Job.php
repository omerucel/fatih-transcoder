<?php

namespace Controller;

use Model\JobMapper;

class Job extends Base
{
    public function get($id)
    {
        $user = $this->getLoggedUser();
        if ($user == null)
        {
            header('Location: /login');
            return;
        }

        $jobMapper = new JobMapper($this->getApplication()->getDbConnection());
        $job = $jobMapper->getJob($user->id, $id);
        $statuses = $jobMapper->getStatuses($user->id, $id);

        echo $this->render('job_detail.twig', array(
            'job' => $job,
            'statuses' => $statuses
        ));
    }
}