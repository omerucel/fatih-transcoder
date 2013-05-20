<?php

namespace Controller;

use Model\FileMapper;
use Aws\S3\S3Client;

class Upload extends Base
{
    public function post()
    {
        $user = $this->getLoggedUser();
        if ($user == null)
        {
            header('HTTP/1.1 401');
            return;
        }

        $nodeConfig = $this->getApplication()->getConfig('node');
        $awsConfig = $this->getApplication()->getConfig('aws');
        $awsClient = S3Client::factory($awsConfig);

        $filePath = $_FILES['file']['tmp_name'];
        $fileName = basename($_FILES['file']['name']);
        $fileHash = md5(time() . $_FILES['file']['name']);

        $mimeType = exec($nodeConfig['bin'] . ' ' . $this->getApplication()->getBasePath() . '/utils/check_mimetype.js ' . $filePath);
        $metadata = exec($nodeConfig['bin'] . ' ' . $this->getApplication()->getBasePath() . '/utils/get_metadata.js "' . $filePath . '"');
        try{
            $metadata = json_decode($metadata);
        }catch(\Exception $e){
            $metadata = array();
        }
        $fileSize = filesize($filePath);

        $awsClient->putObject(array(
            'Bucket' => $awsConfig['bucket'],
            'Key' => $user->id . '/' . $fileHash,
            'SourceFile' => $filePath
        ));

        $awsClient->waitUntilObjectExists(array(
            'Bucket' => $awsConfig['bucket'],
            'Key' => $user->id . '/' . $fileHash
        ));

        $fileMapper = new FileMapper($this->getApplication()->getDbConnection());
        $fileMapper->uploadfile($user->id, $fileHash, $fileName, $mimeType, $fileSize, $metadata);

        header('HTTP/1.1 200');
    }
}