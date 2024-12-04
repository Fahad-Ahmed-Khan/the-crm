<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_104 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        if (!$CI->db->table_exists(db_prefix() . 'lead_manager_conference')) {
            $CI->db->query("CREATE TABLE `" . db_prefix() . "lead_manager_conference` (
                `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `ConferenceSid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                `FriendlyName` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                `StatusCallbackEvent` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                `CallSid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                `Muted` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
                `Hold` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
                `direction` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
                `added_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
        }
        if (!$CI->db->field_exists('type', db_prefix() . 'lead_manager_activity_log')) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "lead_manager_activity_log` CHANGE `type` `type` ENUM('audio_call','video_call','sms','conference_call') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'audio_call'");
        }
        if ($CI->db->field_exists('type', db_prefix() . 'lead_manager_activity_log')) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "lead_manager_activity_log` CHANGE `type` `type` ENUM('audio_call','video_call','sms','conference_call','remark') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'audio_call'");
        }
        if (!$CI->db->field_exists('is_notified', db_prefix() . 'lead_manager_meeting_remark')) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "lead_manager_meeting_remark` ADD `is_notified` TINYINT(4) NOT NULL DEFAULT '0' AFTER `lm_follow_up_date`");
        }
        $query = $CI->db->get_where(db_prefix() . 'customfields', array('slug' => 'staff_is_twilio_number_whats_app_enabled', 'fieldto' => 'staff'));
        if ($query->num_rows() == 0) {
            $data = array(
                'fieldto' => 'staff',
                'name' => 'Is twilio number whats app enabled',
                'slug' => 'staff_is_twilio_number_whats_app_enabled',
                'required' => 0,
                'type' => 'select',
                'options' => 'Enable,Disable',
                'display_inline' => 0,
                'field_order' => 8,
                'active' => 1,
                'show_on_pdf' => 0,
                'show_on_ticket_form' => 0,
                'only_admin' => 0,
                'show_on_table' => 1,
                'show_on_client_portal' => 0,
                'disalow_client_to_edit' => 0,
                'bs_column' => 12,
            );
            $CI->db->insert(db_prefix() . 'customfields', $data);
        }
        $query = $CI->db->get_where(db_prefix() . 'customfields', array('slug' => 'leads_whatsapp_enable', 'fieldto' => 'leads'));
        if ($query->num_rows() == 0) {
            $data = array(
                'fieldto' => 'leads',
                'name' => 'Whatsapp Enable',
                'slug' => 'leads_whatsapp_enable',
                'required' => 1,
                'type' => 'select',
                'options' => 'Enable,Disable',
                'display_inline' => 0,
                'field_order' => 8,
                'active' => 1,
                'show_on_pdf' => 0,
                'show_on_ticket_form' => 0,
                'only_admin' => 0,
                'show_on_table' => 1,
                'show_on_client_portal' => 0,
                'disalow_client_to_edit' => 0,
                'bs_column' => 12,
            );
            $CI->db->insert(db_prefix() . 'customfields', $data);
        }
        $query = $CI->db->get_where(db_prefix() . 'customfields', array('slug' => 'customers_whatsapp_enable', 'fieldto' => 'customers'));
        if ($query->num_rows() == 0) {
            $data = array(
                'fieldto' => 'customers',
                'name' => 'Whatsapp Enable',
                'slug' => 'customers_whatsapp_enable',
                'required' => 1,
                'type' => 'select',
                'options' => 'Enable,Disable',
                'display_inline' => 0,
                'field_order' => 8,
                'active' => 1,
                'show_on_pdf' => 0,
                'show_on_ticket_form' => 0,
                'only_admin' => 0,
                'show_on_table' => 1,
                'show_on_client_portal' => 0,
                'disalow_client_to_edit' => 0,
                'bs_column' => 12,
            );
            $CI->db->insert(db_prefix() . 'customfields', $data);
        }
        $query = $CI->db->get_where(db_prefix() . 'customfields', array('slug' => 'contacts_whatsapp_enable', 'fieldto' => 'contacts'));
        if ($query->num_rows() == 0) {
            $data = array(
                'fieldto' => 'contacts',
                'name' => 'Whatsapp Enable',
                'slug' => 'contacts_whatsapp_enable',
                'required' => 1,
                'type' => 'select',
                'options' => 'Enable,Disable',
                'display_inline' => 0,
                'field_order' => 8,
                'active' => 1,
                'show_on_pdf' => 0,
                'show_on_ticket_form' => 0,
                'only_admin' => 0,
                'show_on_table' => 1,
                'show_on_client_portal' => 0,
                'disalow_client_to_edit' => 0,
                'bs_column' => 12,
            );
            $CI->db->insert(db_prefix() . 'customfields', $data);
        }
        if (!$CI->db->table_exists(db_prefix() . 'lead_manager_whatsapp')) {
            $CI->db->query("CREATE TABLE `" . db_prefix() . "lead_manager_whatsapp` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `msg_sid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
            `from_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
            `to_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
            `to_id` int(11) DEFAULT NULL,
            `from_id` int(11) DEFAULT NULL,
            `sms_direction` enum('outgoing','incoming') COLLATE utf8_unicode_ci NOT NULL,
            `sms_status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
            `sms_body` longtext COLLATE utf8_unicode_ci,
            `api_response` longtext COLLATE utf8_unicode_ci,
            `is_client` tinyint(4) NOT NULL,
            `is_read` ENUM('no','yes') NULL DEFAULT NULL ,
            `is_files` int(11) NOT NULL DEFAULT '0',
            `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
        }
        defined('LEAD_MANAGER_WHATSAPP_FOLDER') or define('LEAD_MANAGER_WHATSAPP_FOLDER', FCPATH . 'uploads/lead_manager/whatsapp' . '/');;
        if (!is_dir(LEAD_MANAGER_WHATSAPP_FOLDER)) {
            mkdir(LEAD_MANAGER_WHATSAPP_FOLDER, 0777, TRUE);
            fopen(LEAD_MANAGER_WHATSAPP_FOLDER . 'index.html', 'w');
            $fp = fopen(LEAD_MANAGER_WHATSAPP_FOLDER . 'index.html', 'a+');
            if ($fp) {
                fclose($fp);
            }
        }
        if (!$CI->db->table_exists(db_prefix() . 'lead_manager_whatsapp_files')) {
            $CI->db->query("CREATE TABLE `" . db_prefix() . "lead_manager_whatsapp_files` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `from_id` int(11) NOT NULL,
              `to_id` int(11) NOT NULL,
              `file_name` text COLLATE utf8_unicode_ci NOT NULL,
              `filetype` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `dateadded` datetime NOT NULL,
              PRIMARY KEY (id)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
        }
        if (!$CI->db->field_exists('to_cc', db_prefix() . 'lead_manager_mailbox')) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "lead_manager_mailbox` ADD `to_cc` VARCHAR(255) NULL DEFAULT NULL AFTER `message_id`;");
        }
        if ($CI->db->field_exists('imap_port', db_prefix() . 'lead_manager_mailbox_settings')) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "lead_manager_mailbox_settings` CHANGE `imap_port` `imap_port` varchar(100) NOT NULL DEFAULT '993' AFTER `imap_folder`");
        }
        if (!$CI->db->table_exists(db_prefix() . 'lead_manager_whatsapp_templates')) {
            $CI->db->query("CREATE TABLE " . db_prefix() . "lead_manager_whatsapp_templates (
              `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `template_id` VARCHAR(255) NULL DEFAULT NULL COMMENT 'id from api',
              `template_name` varchar(255) NULL DEFAULT NULL,
              `language` varchar(50) NULL DEFAULT NULL,
              `status` varchar(50) NULL DEFAULT NULL,
              `category` varchar(100) NULL DEFAULT NULL,
              `header_data_format` varchar(10) NULL DEFAULT NULL,
              `header_data_text` text DEFAULT NULL,
              `header_params_count` int(11) NULL DEFAULT NULL,
              `body_data` text NULL DEFAULT NULL,
              `body_params_count` int(11) NULL DEFAULT NULL,
              `footer_data` text DEFAULT NULL,
              `footer_params_count` int(11) NULL DEFAULT NULL,
              `buttons_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL CHECK (json_valid(`buttons_data`))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }
        $row_exists = $CI->db->query('SELECT * FROM ' . db_prefix() . 'emailtemplates where type = "lead_manager_email" and slug = "lead-manager-send-email-to-lead" and language = "english";')->row();
        if (!$row_exists) {
            $message = '';
            $CI->db->query("INSERT INTO `" . db_prefix() . "emailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES ('lead_manager_email', 'lead-manager-send-email-to-lead', 'english', 'Lead Manager Email', 'New Email','" . $message . "','', NULL, 0, 1, 0);");
        }
        $row_exists = $CI->db->query('SELECT * FROM ' . db_prefix() . 'emailtemplates where type = "lead_manager_email" and slug = "lead-manager-send-email-to-customer" and language = "english";')->row();
        if (!$row_exists) {
            $message = '';
            $CI->db->query("INSERT INTO `" . db_prefix() . "emailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES ('lead_manager_email', 'lead-manager-send-email-to-customer', 'english', 'Lead Manager Email', 'New Email','" . $message . "','', NULL, 0, 1, 0);");
        }
        if (!$CI->db->table_exists(db_prefix() . 'lead_manager_user_api')) {
            $CI->db->query("CREATE TABLE " . db_prefix() . "lead_manager_user_api (
            `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `staff_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
            `token` VARCHAR(255) NOT NULL,
            `expiration_date` DATETIME NOT NULL
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        }
        if (total_rows(db_prefix() . 'emailtemplates', ['type' => 'lead_manager', 'lead-manager-send-to-staff'])) {
            $CI->db->where(['type' => 'lead_manager', 'lead-manager-send-to-staff']);
            $CI->db->update(db_prefix() . "emailtemplates", ['type' => 'lead_manager_meeting']);
        }
        if (total_rows(db_prefix() . 'emailtemplates', ['type' => 'lead_manager', 'lead-manager-send-to-customer'])) {
            $CI->db->where(['type' => 'lead_manager', 'lead-manager-send-to-customer']);
            $CI->db->update(db_prefix() . "emailtemplates", ['type' => 'lead_manager_meeting']);
        }
        $template_exists_lead = $CI->db->query('SELECT * FROM ' . db_prefix() . 'emailtemplates where type = "lead_manager_meeting" and slug = "lead-manager-send-to-lead" and language = "english";')->row();
        $lead_template = '<html class="no-js" lang=""><head><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@100;300;400;500;700;900&display=swap" rel="stylesheet"> </head> <body style=" font-family: "Noto Sans KR", sans-serif;margin: 0; padding: 0; overflow-x: hidden; background-color: #f4f5f9; padding: 35px;"> <section style="box-shadow: 0 4px 8px 0 rgb(241 241 241 / 20%), 0 6px 20px 0 rgb(212 212 212 / 19%); background-color: white; padding: 20px; max-width: 46%; margin: 0 auto; border: 1px solid #e4e4e4;"> <div class=" margin: 10px 6px; margin-bottom: 0;"> <h4 style="margin: 0; font-size: 14px; text-transform: uppercase; font-weight: 500;">Hi &nbsp;<b>{lead_name}</b>,</h4> <p style=" margin: 6px 0; font-size: 14px;">{staff_name} Sent You a Meeting details</p></div><table class="table" style=" width: 100%; margin-top: 30px;"> <tbody><tr> <th style=" padding: 8px 0; vertical-align: top; border-top: 1px solid #dee2e6; text-align: left; font-size: 14px; color: #101010; position: relative; left: 2px; width: 25%;">Staff Email :</th> <td style=" border-top: 1px solid #dee2e6; font-size: 14px; padding-left: 15px;">{staff_email}</td></tr><tr> <th style=" padding: 8px 0; vertical-align: top; border-top: 1px solid #dee2e6; text-align: left; font-size: 14px; color: #101010; position: relative; left: 2px; width: 25%;">Staff Contact No :</th> <td style=" border-top: 1px solid #dee2e6; font-size: 14px; padding-left: 15px;">{staff_phonenumber}</td></tr><tr> <th style=" padding: 8px 0; vertical-align: top; border-top: 1px solid #dee2e6; text-align: left; font-size: 14px; color: #101010; position: relative; left: 2px; width: 25%;">Meeting agenda :</th> <td style=" border-top: 1px solid #dee2e6; font-size: 14px; padding-left: 15px;">{topic}</td></tr><tr> <th style=" padding: 8px 0; vertical-align: top; border-top: 1px solid #dee2e6; text-align: left; font-size: 14px; color: #101010; position: relative; left: 2px; width: 25%;">Description :</th> <td style=" border-top: 1px solid #dee2e6; font-size: 14px; padding-left: 15px;">{meeting_description}</td></tr><tr> <th style=" padding: 8px 0; vertical-align: top; border-top: 1px solid #dee2e6; text-align: left; font-size: 14px; color: #101010; position: relative; left: 2px; width: 25%;">Meeting Created :</th> <td style=" border-top: 1px solid #dee2e6; font-size: 14px; padding-left: 15px;">{created_at}</td></tr><tr> <th style=" padding: 8px 0; vertical-align: top; border-top: 1px solid #dee2e6; text-align: left; font-size: 14px; color: #101010; position: relative; left: 2px; width: 25%;">Meeting scheduled :</th> <td style=" border-top: 1px solid #dee2e6; font-size: 14px; padding-left: 15px;">{meeting_time}</td></tr><tr> <th style=" padding: 8px 0; vertical-align: top; border-top: 1px solid #dee2e6; text-align: left; font-size: 14px; color: #101010;position: relative; left: 2px; width: 25%;">Meeting ID :</th> <td style=" border-top: 1px solid #dee2e6;font-size: 14px;padding-left: 15px;">{meeting_id}</td></tr><tr> <th style="padding:8px 0;vertical-align:top;border-top:1px solid #dee2e6;text-align:left;font-size:14px;color:#101010;width:25%">Duration :</th> <td style="border-top:1px solid #dee2e6;font-size:14px;padding-left:15px">{meeting_duration} Minutes</td></tr></tbody> </table> <div > <a href="{join_url}" target="_blank"><button style="background-color: #2d8cff; border-color: transparent; padding: 11px 22px; color: white; border-radius: 7px; margin: 18px 0; font-weight: 500; font-size: 18px; cursor: pointer;" type="button" class="btn btn-secondary">Join Meeting</button></a> </div><hr style=" border: 1px solid #dedede;"> <div style="text-align: right;padding: 0 0px;"> <p style=" margin: 2px;">Thank You for choosing Lead Manager</p><a href="https://zonvoir.com/"><b>Powered by zonvoir</b></a> </div></section> </body></html>';
        if (!$template_exists_lead) {
            $CI->db->query("INSERT INTO `" . db_prefix() . "emailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES ('lead_manager_meeting', 'lead-manager-send-to-lead', 'english', 'Lead Manager (Sent Zoom Link)', 'Lead Manager Sent Zoom Link','" . $lead_template . "','', NULL, 0, 1, 0);");
        }
        if ($CI->db->field_exists('mail_date', db_prefix() . 'lead_manager_mailbox')) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "lead_manager_mailbox` CHANGE `mail_date` `mail_date` DATETIME NULL DEFAULT NULL");
        }
        if ($CI->db->field_exists('sms_date', db_prefix() . 'lead_manager_conversation')) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "lead_manager_conversation` CHANGE `sms_date` `sms_date` DATETIME NULL DEFAULT NULL");
        }
    }
}
