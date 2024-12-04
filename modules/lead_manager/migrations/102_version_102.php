<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_102 extends App_module_migration
{
    public function up()
    {
       $CI = &get_instance();

       if (!$CI->db->table_exists(db_prefix() . 'lead_manager_conversation')) {
        $CI->db->query("CREATE TABLE `".db_prefix() ."lead_manager_conversation` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `msg_service_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
          `msg_sid` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
          `from_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
          `to_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
          `to_id` int(11) DEFAULT NULL,
          `from_id` int(11) DEFAULT NULL,
          `sms_direction` enum('outgoing','incoming') COLLATE utf8_unicode_ci NOT NULL,
          `sms_status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
          `sms_body` longtext COLLATE utf8_unicode_ci,
          `api_response` longtext COLLATE utf8_unicode_ci,
          `is_read` ENUM('no','yes') NULL DEFAULT NULL ,
          `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
    }
    
    $template_exists_customer = $CI->db->query('SELECT * FROM '.db_prefix() . 'emailtemplates where type = "lead_manager" and slug = "lead-manager-send-to-customer" and language = "english";')->row();
    $customer_template='<html class="no-js" lang=""><head><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@100;300;400;500;700;900&display=swap" rel="stylesheet"> </head> <body style=" font-family: "Noto Sans KR", sans-serif;margin: 0; padding: 0; overflow-x: hidden; background-color: #f4f5f9; padding: 35px;"> <section style="box-shadow: 0 4px 8px 0 rgb(241 241 241 / 20%), 0 6px 20px 0 rgb(212 212 212 / 19%); background-color: white; padding: 20px; max-width: 46%; margin: 0 auto; border: 1px solid #e4e4e4;"> <div class=" margin: 10px 6px; margin-bottom: 0;"> <h4 style="margin: 0; font-size: 14px; text-transform: uppercase; font-weight: 500;">Hi &nbsp;<b>{lead_name}</b>,</h4> <p style=" margin: 6px 0; font-size: 14px;">{staff_name} Sent You a Meeting details</p></div><table class="table" style=" width: 100%; margin-top: 30px;"> <tbody><tr> <th style=" padding: 8px 0; vertical-align: top; border-top: 1px solid #dee2e6; text-align: left; font-size: 14px; color: #101010; position: relative; left: 2px; width: 25%;">Staff Email :</th> <td style=" border-top: 1px solid #dee2e6; font-size: 14px; padding-left: 15px;">{staff_email}</td></tr><tr> <th style=" padding: 8px 0; vertical-align: top; border-top: 1px solid #dee2e6; text-align: left; font-size: 14px; color: #101010; position: relative; left: 2px; width: 25%;">Staff Contact No :</th> <td style=" border-top: 1px solid #dee2e6; font-size: 14px; padding-left: 15px;">{staff_phonenumber}</td></tr><tr> <th style=" padding: 8px 0; vertical-align: top; border-top: 1px solid #dee2e6; text-align: left; font-size: 14px; color: #101010; position: relative; left: 2px; width: 25%;">Meeting agenda :</th> <td style=" border-top: 1px solid #dee2e6; font-size: 14px; padding-left: 15px;">{topic}</td></tr><tr> <th style=" padding: 8px 0; vertical-align: top; border-top: 1px solid #dee2e6; text-align: left; font-size: 14px; color: #101010; position: relative; left: 2px; width: 25%;">Description :</th> <td style=" border-top: 1px solid #dee2e6; font-size: 14px; padding-left: 15px;">{meeting_description}</td></tr><tr> <th style=" padding: 8px 0; vertical-align: top; border-top: 1px solid #dee2e6; text-align: left; font-size: 14px; color: #101010; position: relative; left: 2px; width: 25%;">Meeting Created :</th> <td style=" border-top: 1px solid #dee2e6; font-size: 14px; padding-left: 15px;">{created_at}</td></tr><tr> <th style=" padding: 8px 0; vertical-align: top; border-top: 1px solid #dee2e6; text-align: left; font-size: 14px; color: #101010; position: relative; left: 2px; width: 25%;">Meeting scheduled :</th> <td style=" border-top: 1px solid #dee2e6; font-size: 14px; padding-left: 15px;">{meeting_time}</td></tr><tr> <th style=" padding: 8px 0; vertical-align: top; border-top: 1px solid #dee2e6; text-align: left; font-size: 14px; color: #101010;position: relative; left: 2px; width: 25%;">Meeting ID :</th> <td style=" border-top: 1px solid #dee2e6;font-size: 14px;padding-left: 15px;">{meeting_id}</td></tr><tr> <th style="padding:8px 0;vertical-align:top;border-top:1px solid #dee2e6;text-align:left;font-size:14px;color:#101010;width:25%">Duration :</th> <td style="border-top:1px solid #dee2e6;font-size:14px;padding-left:15px">{meeting_duration} Minutes</td></tr></tbody> </table> <div > <a href="{join_url}" target="_blank"><button style="background-color: #2d8cff; border-color: transparent; padding: 11px 22px; color: white; border-radius: 7px; margin: 18px 0; font-weight: 500; font-size: 18px; cursor: pointer;" type="button" class="btn btn-secondary">Join Meeting</button></a> </div><hr style=" border: 1px solid #dedede;"> <div style="text-align: right;padding: 0 0px;"> <p style=" margin: 2px;">Thank You for choosing Lead Manager</p><a href="https://zonvoir.com/"><b>Powered by zonvoir</b></a> </div></section> </body></html>';
    if(!$template_exists_customer){
        $CI->db->query("INSERT INTO `".db_prefix() ."emailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES ('lead_manager', 'lead-manager-send-to-customer', 'english', 'Lead Manager (Sent Zoom Link)', 'Lead Manager Sent Zoom Link','".$customer_template."','', NULL, 0, 1, 0);");
    }else{
        $CI->db->where('emailtemplateid',$template_exists_customer->emailtemplateid);
        $CI->db->update(db_prefix() ."emailtemplates",['message' => $customer_template]);
    }

    $template_exists_staff = $CI->db->query('SELECT * FROM '.db_prefix() . 'emailtemplates where type = "lead_manager" and slug = "lead-manager-send-to-staff" and language = "english";')->row();
    $staff_template='<html class="no-js" lang=""> <head> <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@100;300;400;500;700;900&display=swap" rel="stylesheet"> </head> <body style=" font-family: "Noto Sans KR", sans-serif;margin: 0; padding: 0; overflow-x: hidden; background-color: #f4f5f9; padding: 35px;"> <section style="box-shadow: 0 4px 8px 0 rgb(241 241 241 / 20%), 0 6px 20px 0 rgb(212 212 212 / 19%); background-color: white; padding: 20px; max-width: 46%; margin: 0 auto; border: 1px solid #e4e4e4;"> <div class=" margin: 10px 6px; margin-bottom: 0;"> <h4 style="margin: 0; font-size: 14px; text-transform: uppercase; font-weight: 500;">Hi &nbsp;<b>{staff_name}</b>,</h4> <p style=" margin: 6px 0; font-size: 14px;">You created a Meeting for <b> {lead_name} </b> at {created_at}</p></div><table class="table" style=" width: 100%; margin-top: 30px;"> <tbody><tr> <th style=" padding: 8px 0; vertical-align: top; border-top: 1px solid #dee2e6; text-align: left; font-size: 14px; color: #101010; position: relative; left: 2px; width: 25%;">Lead Email :</th> <td style=" border-top: 1px solid #dee2e6; font-size: 14px; padding-left: 15px;">{lead_email}</td></tr><tr> <th style=" padding: 8px 0; vertical-align: top; border-top: 1px solid #dee2e6; text-align: left; font-size: 14px; color: #101010; position: relative; left: 2px; width: 25%;">Lead Contact No. :</th> <td style=" border-top: 1px solid #dee2e6; font-size: 14px; padding-left: 15px;">{lead_phonenumber}</td></tr><tr> <th style=" padding: 8px 0; vertical-align: top; border-top: 1px solid #dee2e6; text-align: left; font-size: 14px; color: #101010; position: relative; left: 2px; width: 25%;">Meeting agenda :</th> <td style=" border-top: 1px solid #dee2e6; font-size: 14px; padding-left: 15px;">{topic}</td></tr><tr> <th style=" padding: 8px 0; vertical-align: top; border-top: 1px solid #dee2e6; text-align: left; font-size: 14px; color: #101010; position: relative; left: 2px; width: 25%;">Description :</th> <td style=" border-top: 1px solid #dee2e6; font-size: 14px; padding-left: 15px;">{meeting_description}</td></tr><tr> <th style=" padding: 8px 0; vertical-align: top; border-top: 1px solid #dee2e6; text-align: left; font-size: 14px; color: #101010; position: relative; left: 2px; width: 25%;">Meeting scheduled :</th> <td style=" border-top: 1px solid #dee2e6; font-size: 14px; padding-left: 15px;">{meeting_time}</td></tr><tr> <th style=" padding: 8px 0; vertical-align: top; border-top: 1px solid #dee2e6; text-align: left; font-size: 14px; color: #101010;position: relative; left: 2px; width: 25%;">Meeting ID :</th> <td style=" border-top: 1px solid #dee2e6;font-size: 14px;padding-left: 15px;">{meeting_id}</td></tr><tr> <th style="padding:8px 0;vertical-align:top;border-top:1px solid #dee2e6;text-align:left;font-size:14px;color:#101010;width:25%">Duration :</th> <td style="border-top:1px solid #dee2e6;font-size:14px;padding-left:15px">{meeting_duration} Minutes</td></tr></tbody> </table> <div > <a href="{join_url}" target="_blank"><button style="background-color: #7cb342; border-color: transparent; padding: 11px 22px; color: white; border-radius: 7px; margin: 18px 0; font-weight: 500; font-size: 18px; cursor: pointer;" type="button" class="btn btn-secondary">Start Meeting</button></a> </div><hr style=" border: 1px solid #dedede;"> <div style="text-align: right;padding: 0 0px;"> <p style=" margin: 2px;">Thank You for choosing Lead Manager</p><a href="https://zonvoir.com/"><b>Powered by zonvoir</b></a> </div></section> </body></html>';
    if(!$template_exists_staff){
        $CI->db->query("INSERT INTO `".db_prefix() ."emailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES ('lead_manager', 'lead-manager-send-to-staff', 'english', 'Lead Manager (Sent Zoom Link)', 'Lead Manager Sent Zoom Link','".$staff_template."','', NULL, 0, 1, 0);");
    }else{
        $CI->db->where('emailtemplateid',$template_exists_staff->emailtemplateid);
        $CI->db->update(db_prefix() ."emailtemplates",['message' => $staff_template]);
    }

    if ($CI->db->field_exists('meeting_id', db_prefix().'lead_manager_zoom_meeting')) {

        $CI->db->query("ALTER TABLE ".db_prefix()."lead_manager_zoom_meeting CHANGE `meeting_id` `meeting_id` VARCHAR(255) NOT NULL;");
    }
    if ($CI->db->field_exists('status', db_prefix().'lead_manager_zoom_meeting')) {
      $CI->db->query("ALTER TABLE ".db_prefix()."lead_manager_zoom_meeting CHANGE `status` `status` VARCHAR(255) NOT NULL;");
      $CI->db->query("UPDATE ".db_prefix()."lead_manager_zoom_meeting SET `status` = 'waiting';");
    }
    if ($CI->db->field_exists('lm_follow_up_date', db_prefix().'lead_manager_meeting_remark')) {
      $CI->db->query("ALTER TABLE ".db_prefix()."lead_manager_meeting_remark CHANGE `lm_follow_up_date` `lm_follow_up_date` DATETIME NULL DEFAULT NULL;");
    }
    if (!$CI->db->field_exists('is_client', db_prefix().'lead_manager_zoom_meeting')) {
      $CI->db->query("ALTER TABLE ".db_prefix()."lead_manager_zoom_meeting ADD `is_client` TINYINT NOT NULL DEFAULT '0' AFTER `status`;");
    }
    if (!$CI->db->field_exists('is_client', db_prefix().'lead_manager_conversation')) {
      $CI->db->query("ALTER TABLE ".db_prefix()."lead_manager_conversation ADD `is_client` TINYINT NOT NULL DEFAULT '0' AFTER `api_response`;");
    }
    if (!$CI->db->field_exists('is_client', db_prefix().'lead_manager_activity_log')) {
      $CI->db->query("ALTER TABLE ".db_prefix()."lead_manager_activity_log ADD `is_client` TINYINT NOT NULL DEFAULT '0' AFTER `call_duration`;");
    }
}
}