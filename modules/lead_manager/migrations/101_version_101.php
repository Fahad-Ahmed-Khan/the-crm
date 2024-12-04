<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_101 extends App_module_migration
{
    public function up()
    {
    	$CI = &get_instance();

        $leadManagerMeetingRemarkTable = db_prefix() . 'lead_manager_meeting_remark';

        $leadsTable = db_prefix() . 'leads';

        if (!$CI->db->field_exists('lm_follow_up', $leadsTable)) {

            $CI->db->query("ALTER TABLE `" . $leadsTable . "` ADD `lm_follow_up` tinyint(4) NOT NULL DEFAULT 0;");

        }

        if (!$CI->db->field_exists('lm_follow_up_date', $leadManagerMeetingRemarkTable)) {

            $CI->db->query("ALTER TABLE `" . $leadManagerMeetingRemarkTable . "` ADD `lm_follow_up_date` VARCHAR(255) DEFAULT NULL;");

        }

        
    }
}