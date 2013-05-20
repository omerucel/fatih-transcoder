<?php

namespace Model;

class FileMapper extends BaseMapper
{
    public function uploadfile($user_id, $file_hash, $file_name, $mime_type, $file_size, $metadata = array())
    {
        $video_codec = '';
        $video_fps = 0;
        $video_bitrate = 0;

        $audio_codec = '';
        $audio_bitrate = 0;
        $audio_samplerate = 0;
        $audio_channels = 0;

        if (isset($metadata->video) && isset($metadata->video->codec))
            $video_codec = $metadata->video->codec;

        if (isset($metadata->video) && isset($metadata->video->fps))
            $video_fps = (int)$metadata->video->fps;

        if (isset($metadata->video) && isset($metadata->video->bitrate))
            $video_bitrate = (int)$metadata->video->bitrate;

        if (isset($metadata->audio) && isset($metadata->audio->codec))
            $audio_codec = $metadata->audio->codec;

        if (isset($metadata->audio) && isset($metadata->audio->bitrate))
            $audio_bitrate = (int)$metadata->audio->bitrate;

        if (isset($metadata->audio) && isset($metadata->audio->samplerate))
            $audio_samplerate = (int)$metadata->audio->samplerate;

        if (isset($metadata->audio) && isset($metadata->audio->channels))
            $audio_channels = (int)$metadata->audio->channels;

        $statement = $this->pdo->prepare('INSERT INTO user_file(user_id, hash, name, mime_type, size,'
            . 'audio_codec, audio_bitrate, audio_samplerate, audio_channels,'
            . 'video_codec, video_bitrate, video_fps'
            . ') VALUES (:user_id, :hash, :file_name, :mime_type, :file_size,'
            . ':audio_codec, :audio_bitrate, :audio_samplerate, :audio_channels,'
            . ':video_codec, :video_bitrate, :video_fps'
            . ')');
        $statement->bindValue(':user_id', $user_id, \PDO::PARAM_INT);
        $statement->bindValue(':hash', $file_hash);
        $statement->bindValue(':file_name', $file_name);
        $statement->bindValue(':mime_type', $mime_type);
        $statement->bindValue(':file_size', $file_size, \PDO::PARAM_INT);
        $statement->bindValue(':audio_codec', $audio_codec);
        $statement->bindValue(':audio_bitrate', $audio_bitrate, \PDO::PARAM_INT);
        $statement->bindValue(':audio_samplerate', $audio_samplerate, \PDO::PARAM_INT);
        $statement->bindValue(':audio_channels', $audio_channels, \PDO::PARAM_INT);
        $statement->bindValue(':video_codec', $video_codec);
        $statement->bindValue(':video_bitrate', $video_bitrate, \PDO::PARAM_INT);
        $statement->bindValue(':video_fps', $video_fps, \PDO::PARAM_INT);
        $statement->execute();
    }

    public function getStageFiles($user_id)
    {
        $statement = $this->pdo->prepare('SELECT * FROM user_file WHERE user_id = :user_id AND is_output = 0 ORDER BY name ASC');
        $statement->bindValue(':user_id', $user_id, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_OBJ);
    }

    public function getFileInfo($user_id, $id)
    {
        $statement = $this->pdo->prepare('SELECT * FROM user_file WHERE user_id = :user_id AND id = :id');
        $statement->bindValue(':user_id', $user_id, \PDO::PARAM_INT);
        $statement->bindValue(':id', $id, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchObject();
    }

    public function deleteFile($user_id, $id)
    {
        $ids = explode(',', $id);
        $statement = $this->pdo->prepare('DELETE FROM user_file WHERE user_id = :user_id AND id = :id');

        foreach($ids as $id)
        {
            $statement->bindValue(':user_id', $user_id, \PDO::PARAM_INT);
            $statement->bindValue(':id', $id, \PDO::PARAM_INT);
            $statement->execute();
        }
    }

    public function getProductionFiles($user_id)
    {
        $statement = $this->pdo->prepare('SELECT * FROM user_file WHERE user_id = :user_id AND is_output = 1 ORDER BY name ASC');
        $statement->bindValue(':user_id', $user_id, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_OBJ);
    }
}