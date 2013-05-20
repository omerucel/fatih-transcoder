SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

DROP SCHEMA IF EXISTS `transcoder` ;
CREATE SCHEMA IF NOT EXISTS `transcoder` DEFAULT CHARACTER SET utf8 ;
USE `transcoder` ;

-- -----------------------------------------------------
-- Table `transcoder`.`session`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `transcoder`.`session` ;

CREATE  TABLE IF NOT EXISTS `transcoder`.`session` (
  `id` BIGINT(19) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `user_id` BIGINT(19) UNSIGNED NOT NULL ,
  `token` VARCHAR(64) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `transcoder`.`user`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `transcoder`.`user` ;

CREATE  TABLE IF NOT EXISTS `transcoder`.`user` (
  `id` BIGINT(19) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `email` VARCHAR(255) NOT NULL ,
  `password` VARCHAR(64) NOT NULL ,
  `salt` VARCHAR(64) NOT NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
AUTO_INCREMENT = 2
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `transcoder`.`user_file`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `transcoder`.`user_file` ;

CREATE  TABLE IF NOT EXISTS `transcoder`.`user_file` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `user_id` BIGINT UNSIGNED NOT NULL ,
  `hash` VARCHAR(32) NOT NULL ,
  `name` VARCHAR(255) NOT NULL ,
  `mime_type` VARCHAR(45) NOT NULL ,
  `size` BIGINT UNSIGNED NOT NULL ,
  `is_output` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_user_file_user_id_user_id_idx` (`user_id` ASC) ,
  CONSTRAINT `fk_user_file_user_id_user_id`
    FOREIGN KEY (`user_id` )
    REFERENCES `transcoder`.`user` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `transcoder`.`job`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `transcoder`.`job` ;

CREATE  TABLE IF NOT EXISTS `transcoder`.`job` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `user_id` BIGINT UNSIGNED NOT NULL ,
  `file_id` BIGINT UNSIGNED NOT NULL ,
  `output_file_id` BIGINT UNSIGNED NULL DEFAULT NULL ,
  `format` VARCHAR(4) NOT NULL ,
  `is_complete` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 ,
  `has_error` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 ,
  `worker_name` VARCHAR(45) NULL ,
  `error_message` TEXT NULL ,
  `started_at` DATETIME NULL DEFAULT NULL ,
  `completed_at` DATETIME NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_job_user_id_user_id_idx` (`user_id` ASC) ,
  INDEX `fk_job_file_id_file_id_idx` (`file_id` ASC) ,
  INDEX `fk_job_output_file_id_file_id_idx` (`output_file_id` ASC) ,
  CONSTRAINT `fk_job_user_id_user_id`
    FOREIGN KEY (`user_id` )
    REFERENCES `transcoder`.`user` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_job_file_id_file_id`
    FOREIGN KEY (`file_id` )
    REFERENCES `transcoder`.`user_file` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_job_output_file_id_file_id`
    FOREIGN KEY (`output_file_id` )
    REFERENCES `transcoder`.`user_file` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `transcoder`.`job_status`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `transcoder`.`job_status` ;

CREATE  TABLE IF NOT EXISTS `transcoder`.`job_status` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `job_id` BIGINT UNSIGNED NOT NULL ,
  `status` VARCHAR(45) NOT NULL ,
  `created_at` DATETIME NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_job_status_job_id_job_id_idx` (`job_id` ASC) ,
  CONSTRAINT `fk_job_status_job_id_job_id`
    FOREIGN KEY (`job_id` )
    REFERENCES `transcoder`.`job` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

USE `transcoder` ;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
