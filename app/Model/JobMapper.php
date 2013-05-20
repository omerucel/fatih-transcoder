<?php

namespace Model;

class JobMapper extends BaseMapper
{
    public function newJob($user_id, $file_id, $format, $options)
    {
        $two_pass = isset($options['two_pass']) ? $options['two_pass'] : 0;
        $two_pass = $two_pass == 1 or $two_pass == 0 ? $two_pass : 0;

        $audio_settings = isset($options['audio']) ? $options['audio'] : array();
        $audio_settings = is_array($audio_settings) ? $audio_settings : array();

        $video_settings = isset($options['video']) ? $options['video'] : array();
        $video_settings = is_array($video_settings) ? $video_settings : array();

        $audio_codec_active = isset($audio_settings['codec_active']) ? 1 : 0;
        $audio_bitrate_active = isset($audio_settings['bitrate_active']) ? 1 : 0;
        $audio_samplerate_active = isset($audio_settings['samplerate_active']) ? 1 : 0;
        $audio_channels_active = isset($audio_settings['channels_active']) ? 1 : 0;

        $audio_codec = $audio_codec_active === 1 ? $audio_settings['codec'] : '';
        $audio_bitrate = $audio_bitrate_active === 1 ? $audio_settings['bitrate'] : 0;
        $audio_samplerate = $audio_samplerate_active === 1 ? $audio_settings['samplerate'] : 0;
        $audio_channels = $audio_channels_active === 1 ? $audio_settings['channels'] : 0;

        $video_codec_active = isset($video_settings['codec_active']) ? 1 : 0;
        $video_bitrate_active = isset($video_settings['bitrate_active']) ? 1 : 0;
        $video_fps_active = isset($video_settings['fps_active']) ? 1 : 0;

        $video_codec = $video_codec_active === 1 ? $video_settings['codec'] : '';
        $video_bitrate = $video_bitrate_active === 1 ? $video_settings['bitrate'] : 0;
        $video_fps = $video_fps_active === 1 ? $video_settings['fps'] : 0;

        $statement = $this->pdo->prepare('INSERT INTO job(user_id, file_id, format, two_pass,'
            . 'audio_codec_active, audio_bitrate_active, audio_samplerate_active, audio_channels_active,'
            . 'audio_codec, audio_bitrate, audio_samplerate, audio_channels,'
            . 'video_codec_active, video_fps_active, video_bitrate_active,'
            . 'video_codec, video_fps, video_bitrate'
            . ') VALUES(:user_id, :file_id, :format, :two_pass,'
            . ':audio_codec_active, :audio_bitrate_active, :audio_samplerate_active, :audio_channels_active,'
            . ':audio_codec, :audio_bitrate, :audio_samplerate, :audio_channels,'
            . ':video_codec_active, :video_fps_active, :video_bitrate_active,'
            . ':video_codec, :video_fps, :video_bitrate'
            . ')');
        $statement->bindValue(':user_id', $user_id, \PDO::PARAM_INT);
        $statement->bindValue(':file_id', $file_id, \PDO::PARAM_INT);
        $statement->bindValue(':format', $format);
        $statement->bindValue(':two_pass', $two_pass, \PDO::PARAM_INT);
        $statement->bindValue(':audio_codec_active', $audio_codec_active, \PDO::PARAM_INT);
        $statement->bindValue(':audio_bitrate_active', $audio_bitrate_active, \PDO::PARAM_INT);
        $statement->bindValue(':audio_samplerate_active', $audio_samplerate_active, \PDO::PARAM_INT);
        $statement->bindValue(':audio_channels_active', $audio_channels_active, \PDO::PARAM_INT);
        $statement->bindValue(':audio_codec', $audio_codec);
        $statement->bindValue(':audio_bitrate', $audio_bitrate, \PDO::PARAM_INT);
        $statement->bindValue(':audio_samplerate', $audio_samplerate, \PDO::PARAM_INT);
        $statement->bindValue(':audio_channels', $audio_channels, \PDO::PARAM_INT);

        $statement->bindValue(':video_codec_active', $video_codec_active, \PDO::PARAM_INT);
        $statement->bindValue(':video_bitrate_active', $video_bitrate_active, \PDO::PARAM_INT);
        $statement->bindValue(':video_fps_active', $video_fps_active, \PDO::PARAM_INT);
        $statement->bindValue(':video_codec', $video_codec);
        $statement->bindValue(':video_bitrate', $video_bitrate, \PDO::PARAM_INT);
        $statement->bindValue(':video_fps', $video_fps, \PDO::PARAM_INT);
        $statement->execute();

        return $this->pdo->lastInsertId('job');
    }

    public function getHistory($user_id, $is_complete)
    {
        $statement = $this->pdo->prepare('SELECT job.id, job.is_complete, job.has_error, job.format, job.completed_at, input.name as input_name, output.name as output_name FROM job '
            . 'INNER JOIN user_file as input ON input.id = job.file_id '
            . 'LEFT JOIN user_file as output ON output.id = job.output_file_id '
            . 'WHERE job.user_id = :user_id AND job.is_complete = :is_complete ORDER BY job.completed_at DESC');
        $statement->bindValue(':user_id', $user_id, \PDO::PARAM_INT);
        $statement->bindValue(':is_complete', $is_complete, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_OBJ);
    }

    public function getJobs($user_id)
    {
        $statement = $this->pdo->prepare('SELECT user_file.name, job.worker_name, TIMEDIFF(NOW(), job.started_at) as elapsed_time, job.progress, '
            . '(SELECT status FROM job_status WHERE job_id = job.id ORDER BY created_at DESC, id DESC LIMIT 1) as status '
            . 'FROM job '
            . 'INNER JOIN user_file ON user_file.id = job.file_id '
            . 'WHERE job.user_id = :user_id AND job.is_complete = 0 ORDER BY job.id ASC');
        $statement->bindValue(':user_id', $user_id, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_OBJ);
    }

    public function getJob($user_id, $id)
    {
        $statement = $this->pdo->prepare('SELECT job.id, job.is_complete, job.has_error, job.error_message, job.format, job.completed_at, job.started_at, job.worker_name, TIMEDIFF(job.completed_at, job.started_at) as elapsed_time, input.name as input_name, input.size as input_size, input.mime_type as input_mime_type, output.name as output_name, output.size as output_size, output.mime_type as output_mime_type FROM job '
            . 'INNER JOIN user_file as input ON input.id = job.file_id '
            . 'LEFT JOIN user_file as output ON output.id = job.output_file_id '
            . 'WHERE job.user_id = :user_id AND job.id = :id');

        $statement->bindValue(':user_id', $user_id, \PDO::PARAM_INT);
        $statement->bindValue(':id', $id, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchObject();
    }

    public function getStatuses($user_id, $id)
    {
        $statement = $this->pdo->prepare('SELECT * FROM job_status '
            . 'INNER JOIN job ON job.id = job_status.job_id '
            . 'WHERE job.id = :id AND job.user_id = :user_id ORDER BY job_status.id ASC, job_status.created_at ASC');
        $statement->bindValue(':user_id', $user_id, \PDO::PARAM_INT);
        $statement->bindValue(':id', $id, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_OBJ);
    }
}