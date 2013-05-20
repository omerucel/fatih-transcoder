#-*- coding: utf-8 -*-

import os
import md5
import boto
import json
import uuid
import logging
import gearman
import MySQLdb
import subprocess
import ConfigParser

from converter import Converter
from boto.s3.key import Key

class Config:
    def __init__(self, config_file):
        self.config = ConfigParser.RawConfigParser()
        self.config.read(config_file)

    def get_worker_name(self):
        return self.config.get('Worker', 'name')

    def get_gearman_host(self):
        return self.config.get('Gearman', 'host')

    def get_gearman_port(self):
        return self.config.getint('Gearman', 'port')

    def get_mysql_host(self):
        return self.config.get('MySQL', 'host')

    def get_mysql_port(self):
        return self.config.getint('MySQL', 'port')

    def get_mysql_user(self):
        return self.config.get('MySQL', 'user')

    def get_mysql_password(self):
        return self.config.get('MySQL', 'password')

    def get_mysql_db(self):
        return self.config.get('MySQL', 'db')

    def get_s3_bucket(self):
        return self.config.get('S3', 'bucket')

    def get_s3_key(self):
        return self.config.get('S3', 'key')

    def get_s3_secret(self):
        return self.config.get('S3', 'secret')

    def get_s3_local_tmp_dir(self):
        return self.config.get('S3', 'tmp_dir')

    def get_node_bin(self):
        return self.config.get('Node', 'bin')

    def get_node_tool_check_mimetype(self):
        return self.config.get('Node', 'tool_check_mimetype')

    def get_log_file(self):
        return self.config.get('Log', 'file')

class Database:
    JOB_STATUS_STARTED = 'started'
    JOB_STATUS_FILE_FETCHING = 'file_fetching'
    JOB_STATUS_FILE_FETCHED = 'file_fetched'
    JOB_STATUS_OUTPUT_FILE_SAVING = 'output_file_saving'
    JOB_STATUS_OUTPUT_FILE_SAVED = 'output_file_saved'
    JOB_STATUS_PROCESSING = 'processing'
    JOB_STATUS_PROCESSING_ERROR = 'processing_error'
    JOB_STATUS_CLEANING = 'cleaning'
    JOB_STATUS_CLEANED = 'cleaned'
    JOB_STATUS_SUCCESS = 'success'

    def __init__(self, config):
        self.config = config
        self.connection = MySQLdb.connect(host=self.config.get_mysql_host(),
            port=self.config.get_mysql_port(), user=self.config.get_mysql_user(),
            passwd=self.config.get_mysql_password(), db=self.config.get_mysql_db())

    def fetchoneDict(self, cursor):
        row = cursor.fetchone()
        if row is None:
            return None
        cols = [d[0] for d in cursor.description]
        return dict(zip(cols, row))

    def escape(self, message):
        return self.connection.escape_string(u"%s" %(message))

    def close(self):
        self.connection.close()

    def get_job_info(self, job_id):
        sql = "SELECT user_file.hash, user_file.name, user_file.size, job.format, job.user_id, job.two_pass,"
        sql = sql + "job.audio_codec_active, job.audio_bitrate_active, job.audio_samplerate_active, job.audio_channels_active,"
        sql = sql + "job.audio_codec, job.audio_bitrate, job.audio_samplerate, job.audio_channels,"
        sql = sql + "job.video_codec_active, job.video_fps_active, job.video_bitrate_active,"
        sql = sql + "job.video_codec, job.video_fps, job.video_bitrate "
        sql = sql + "FROM user_file INNER JOIN job ON job.file_id = user_file.id WHERE job.id = %s" %(job_id)
        print "Database::get_job_info : ", sql
        cursor = self.connection.cursor()
        cursor.execute(sql)
        info = self.fetchoneDict(cursor)
        cursor.close()

        audio = {}
        video = {}

        if info['audio_codec_active'] == 1:
            audio['codec'] = info['audio_codec']
        else:
            audio['codec'] = None

        if info['audio_bitrate_active'] == 1:
            audio['bitrate'] = int(info['audio_bitrate'])

        if info['audio_samplerate_active'] == 1:
            audio['samplerate'] = int(info['audio_samplerate'])

        if info['audio_channels_active'] == 1:
            audio['channels'] = int(info['audio_channels'])

        if info['video_codec_active'] == 1:
            video['codec'] = info['video_codec']
        else:
            video['codec'] = None

        if info['video_fps_active'] == 1:
            video['fps'] = int(info['video_fps'])

        if info['video_bitrate_active'] == 1:
            video['bitrate'] = int(info['video_bitrate'])

        if audio.keys().__len__() == 0:
            audio = None

        if video.keys().__len__() == 0:
            video = None

        return {
            'hash': info['hash'],
            'name': info['name'],
            'size': info['size'],
            'format': info['format'],
            'user_id': info['user_id'],
            'two_pass': info['two_pass'],
            'audio': audio,
            'video': video
        }

    def new_job_status(self, job_id, status):
        sql = """INSERT INTO job_status(job_id, status, created_at) VALUES(%s, '%s', NOW())""" %(job_id, status)
        print "Database::new_job_status : ", sql
        cursor = self.connection.cursor()
        cursor.execute(sql)

        # Başlama zamanı ve worker ismi iş tablosunda güncelleniyor.
        if status == Database.JOB_STATUS_STARTED:
            sql = """UPDATE job SET started_at = NOW(), worker_name = '%s' WHERE id = %s""" %(self.config.get_worker_name(), job_id)
            cursor.execute(sql)

        self.connection.commit()
        cursor.close()

    def add_transcoded_file(self, job_id, user_id, file_hash, name, mime_type, size):
        sql = "SELECT COUNT(*) as count FROM user_file WHERE is_output = 1 AND name = '%s'" %(name)
        print "Database::add_transcoded_file : ", sql
        cursor = self.connection.cursor()
        cursor.execute(sql)
        count_info = self.fetchoneDict(cursor)
        if count_info['count'] > 0:
            splitted_name = name.rsplit('.', 1)
            if splitted_name.__len__() == 2:
                name = "%s-%d.%s" %(splitted_name[0], count_info['count'], splitted_name[1])

        sql = """INSERT INTO user_file(user_id, hash, name, mime_type, size, is_output) VALUES(%s, '%s', '%s', '%s', %s, 1)""" %(user_id, file_hash, name, mime_type, size)
        print "Database::add_transcoded_file : ", sql
        cursor.execute(sql)
        file_id = cursor.lastrowid

        sql = """UPDATE job SET output_file_id = %s, is_complete = 1, completed_at = NOW() WHERE id = %s""" %(file_id, job_id)
        cursor.execute(sql)
        self.connection.commit()

        cursor.close()

    def set_error(self, job_id, error_message):
        sql = """UPDATE job SET has_error = 1, is_complete = 1, error_message = '%s', completed_at = NOW() WHERE id = %s""" %(self.escape(error_message), job_id)
        print "Database::set_error : ", sql
        cursor = self.connection.cursor()
        cursor.execute(sql)
        self.connection.commit()
        cursor.close()

    def update_progress(self, job_id, progress):
        sql = """UPDATE job SET progress = %s WHERE id = %s""" %(progress, job_id)
        print "Database::set_error : ", sql
        cursor = self.connection.cursor()
        cursor.execute(sql)
        self.connection.commit()
        cursor.close()

class Worker:
    def __init__(self, config):
        self.config = config
        self.worker = gearman.GearmanWorker(['%s:%s' 
            %(self.config.get_gearman_host(), self.config.get_gearman_port())])
        self.worker.set_client_id(
            self.config.get_worker_name())

    def register_task(self, task, callback):
        self.worker.register_task(task, callback)

    def work(self):
        self.worker.work()

config = Config('config.ini')
logging.basicConfig(filename=config.get_log_file(), level=logging.DEBUG)
worker = Worker(config)
converter = Converter()

def video_transcode(gearman_worker, gearman_job):
    global config
    database = Database(config)
    try:
        data = json.loads(gearman_job.data)
        job_id = data['job_id']

        # İş kontrol ediliyor.
        job_info = database.get_job_info(job_id)
        if job_info == None:
            database.close()
            return ""

        # Durum güncellemesi.
        database.new_job_status(job_id, Database.JOB_STATUS_STARTED)

        # S3 üzerinden dosya alınıyor.
        try:
            database.new_job_status(job_id, Database.JOB_STATUS_FILE_FETCHING)
            s3_client = boto.connect_s3(config.get_s3_key(), config.get_s3_secret())
            bucket = s3_client.get_bucket(config.get_s3_bucket())
            file_key = bucket.get_key('%s/%s' %(job_info['user_id'], job_info['hash']))

            local_file_path = '%s/%s' %(config.get_s3_local_tmp_dir(), uuid.uuid1())
            file_key.get_contents_to_filename(local_file_path)
            database.new_job_status(job_id, Database.JOB_STATUS_FILE_FETCHED)
        except Exception as error:
            database.set_error(job_id, error)
            database.close()
            return ""

        # Dönüştürme işlemi başlıyor.
        try:
            local_converted_file_path = '%s/%s.%s' %(config.get_s3_local_tmp_dir(), uuid.uuid1(), job_info['format'])
            database.new_job_status(job_id, Database.JOB_STATUS_PROCESSING)
            options = {'format': job_info['format']}
            if job_info.has_key('audio'):
                options['audio'] = job_info['audio']
            if job_info.has_key('video'):
                options['video'] = job_info['video']

            #twopass=bool(job_info['two_pass'])
            conversion = converter.convert(local_file_path, local_converted_file_path, options)
            for timecode in conversion:
                database.update_progress(job_id, timecode)
        except Exception as error:
            database.set_error(job_id, error)
            database.close()
            return ""

        # Dönüştürülen dosya S3 üzerine yükleniyor.
        try:
            database.new_job_status(job_id, Database.JOB_STATUS_OUTPUT_FILE_SAVING)
            output_hash = md5.new('%s-%s-%s' %(job_id, job_info['user_id'], uuid.uuid1())).hexdigest()
            output_name = '%s.%s' %(job_info['name'].split('.')[0], job_info['format'])
            output_mime_type = subprocess.check_output([config.get_node_bin(), config.get_node_tool_check_mimetype(), local_converted_file_path]).strip()
            output_size = os.path.getsize(local_converted_file_path)

            output_file = Key(bucket)
            output_file.key = '%s/%s' %(job_info['user_id'], output_hash)
            output_file.set_contents_from_filename(local_converted_file_path)
            database.new_job_status(job_id, Database.JOB_STATUS_OUTPUT_FILE_SAVED)
        except Exception as error:
            database.set_error(job_id, error)
            database.close()
            return ""

        try:
            database.new_job_status(job_id, Database.JOB_STATUS_CLEANING)
            os.remove(local_file_path)
            os.remove(local_converted_file_path)
            database.new_job_status(job_id, Database.JOB_STATUS_CLEANED)

            database.add_transcoded_file(job_id, job_info['user_id'], output_hash, output_name, output_mime_type, output_size)
            database.new_job_status(job_id, Database.JOB_STATUS_SUCCESS)
        except Exception as error:
            database.set_error(job_id, error)
            database.close()
            return ""


    except Exception, e:
        print "Error : ", e

    database.close()
    return ""

worker.register_task('video_transcode', video_transcode)
worker.work()
