<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_103 extends App_module_migration
{
  public function up()
  {
    $CI = &get_instance();

    if (!$CI->db->table_exists(db_prefix() . 'lead_manager_mailbox')) {
      $CI->db->query("CREATE TABLE `". db_prefix() ."lead_manager_mailbox` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `staffid` int(11) NOT NULL,
        `toid` int(11) NOT NULL DEFAULT '0',
        `is_client` tinyint(4) NOT NULL,
        `from_email` varchar(255) NOT NULL,
        `fromName` varchar(100) DEFAULT NULL,
        `to_email` varchar(255) NOT NULL,
        `subject` varchar(255) DEFAULT NULL,
        `direction` enum('inbound','outbound') NOT NULL,
        `message` text NOT NULL,
        `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `status` varchar(100) NOT NULL,
        `is_attachment` tinyint(4) NOT NULL,
        `is_read` tinyint(4) NOT NULL,
        `is_bookmark` tinyint(4) NOT NULL DEFAULT '0',
        `is_favourite` tinyint(4) NOT NULL DEFAULT '0',
        `sequence_id` int(11) DEFAULT NULL,
        `to_cc` varchar(255) DEFAULT NULL,
        `email_size` varchar(255) DEFAULT NULL,
        `mail_date` varchar(255) DEFAULT NULL,
        PRIMARY KEY (id)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
    }
    if (!$CI->db->table_exists(db_prefix() . 'lead_manager_mailbox_settings')) {
      $CI->db->query("CREATE TABLE `". db_prefix() ."lead_manager_mailbox_settings` (
        `staff_id` int(11) NOT NULL,
        `is_smtp` tinyint(4) NOT NULL,
        `smtp_server` varchar(100) NOT NULL,
        `smtp_user` varchar(100) NOT NULL,
        `smtp_password` varchar(255) NOT NULL,
        `smtp_encryption` varchar(100) NOT NULL,
        `smtp_port` int(11) DEFAULT NULL,
        `smtp_fromname` varchar(255) DEFAULT NULL,
        `is_imap` tinyint(4) NOT NULL DEFAULT '0',
        `imap_server` varchar(100) DEFAULT NULL,
        `imap_user` varchar(100) DEFAULT NULL,
        `imap_password` varchar(255) DEFAULT NULL,
        `imap_encryption` varchar(100) DEFAULT NULL,
        `imap_folder` varchar(100) DEFAULT NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");
    }
    if (!$CI->db->table_exists(db_prefix() . 'lead_manager_mailbox_attachments')) {
      $CI->db->query("CREATE TABLE `". db_prefix() ."lead_manager_mailbox_attachments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `staff_id` int(11) NOT NULL,
        `mailbox_id` int(11) NOT NULL,
        `file_name` varchar(255) NOT NULL,
        `filetype` varchar(100) NOT NULL,
        `added_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
      ) ENGINE=InnoDB DEFAULT CHARSET=latin1;");
    }
    if(!is_dir(LEAD_MANAGER_UPLOADS_FOLDER)){
      mkdir(LEAD_MANAGER_UPLOADS_FOLDER, 0777, TRUE);
      fopen(LEAD_MANAGER_UPLOADS_FOLDER . 'index.html', 'w');
      $fp = fopen(LEAD_MANAGER_UPLOADS_FOLDER . 'index.html', 'a+');
      if ($fp) {
        fclose($fp);
      }
    }
    if(!is_dir(LEAD_MANAGER_MAILBOX_FOLDER)){
      mkdir(LEAD_MANAGER_MAILBOX_FOLDER, 0777, TRUE);
      fopen(LEAD_MANAGER_MAILBOX_FOLDER . 'index.html', 'w');
      $fp = fopen(LEAD_MANAGER_MAILBOX_FOLDER . 'index.html', 'a+');
      if ($fp) {
        fclose($fp);
      }
    }
    add_option('lead_manager_imap_check_every', 3);
    add_option('lead_manager_imap_last_checked', 0);
    if (!$CI->db->field_exists('in_reply_to', db_prefix() . 'lead_manager_mailbox')) {
      $CI->db->query("ALTER TABLE `".db_prefix()."lead_manager_mailbox` ADD `in_reply_to` VARCHAR(255) NULL DEFAULT NULL AFTER `mail_date`");
    }
    if (!$CI->db->field_exists('in_references', db_prefix() . 'lead_manager_mailbox')) {
      $CI->db->query("ALTER TABLE `".db_prefix()."lead_manager_mailbox` ADD `in_references`TEXT NULL DEFAULT NULL AFTER `in_reply_to`");
    }
    if (!$CI->db->field_exists('message_id', db_prefix() . 'lead_manager_mailbox')) {
      $CI->db->query("ALTER TABLE `".db_prefix()."lead_manager_mailbox` ADD `message_id` VARCHAR(255) NULL DEFAULT NULL AFTER `in_references`");
    }
  }
}