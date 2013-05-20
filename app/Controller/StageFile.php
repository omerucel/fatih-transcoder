<?php

namespace Controller;

use Aws\S3\S3Client;
use Aws\S3\StreamWrapper;
use Model\FileMapper;

class StageFile extends Base
{
    public function get($id)
    {
        $user = $this->getLoggedUser();
        if ($user == null)
        {
            header('HTTP/1.1 401');
            return;
        }

        $fileMapper = new FileMapper($this->getApplication()->getDbConnection());
        $fileInfo = $fileMapper->getFileInfo($user->id, $id);

        header('Content-Type: ' . $fileInfo->mime_type);
        header('Content-Disposition: attachment; filename="' . $fileInfo->name . '"');

        $awsConfig = $this->getApplication()->getConfig('aws');
        $awsClient = S3Client::factory($awsConfig);

        $result = $awsClient->getObject(array(
            'Bucket' => $awsConfig['bucket'],
            'Key' => $user->id . '/' . $fileInfo->hash
        ));

        echo $result['Body'];
    }

    public function delete($id)
    {
        $user = $this->getLoggedUser();
        if ($user == null)
        {
            header('HTTP/1.1 401');
            return;
        }

        $fileMapper = new FileMapper($this->getApplication()->getDbConnection());
        $fileMapper->deleteFile($user->id, $id);
    }
}