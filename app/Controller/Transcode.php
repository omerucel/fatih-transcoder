<?php

namespace Controller;

use Model\JobMapper;

class Transcode extends Base
{
    public function post()
    {
        $user = $this->getLoggedUser();
        if ($user == null)
        {
            header('HTTP/1.1 401');
            return;
        }

        $gearmanConfig = $this->getApplication()->getConfig('gearman');

        $id = isset($_POST['id']) ? $_POST['id'] : 0;
        $format = isset($_POST['format']) ? $_POST['format'] : '';
        $two_pass = isset($_POST['two_pass']) ? (int)$_POST['two_pass'] : 0;
        $audio_settings = isset($_POST['audio']) ? $_POST['audio'] : array();
        $video_settings = isset($_POST['video']) ? $_POST['video'] : array();

        $options = array(
            'two_pass' => $two_pass,
            'audio' => $audio_settings,
            'video' => $video_settings
        );

        $jobMapper = new JobMapper($this->getApplication()->getDbConnection());
        $jobId = $jobMapper->newJob($user->id, $id, $format, $options);

        $gmclient = new \GearmanClient();
        $gmclient->addServer($gearmanConfig['host'], $gearmanConfig['port']);

        $data = json_encode(array(
            'job_id' => $jobId
        ));

        $gmclient->doBackground('video_transcode', $data);
    }
}