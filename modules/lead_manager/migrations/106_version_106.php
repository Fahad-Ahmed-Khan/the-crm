<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_106 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        add_option('lm_zoom_account_id', 0);
        if (!$CI->db->table_exists(db_prefix() . 'lead_manager_zoom_meeting_access_token')) {
            $CI->db->query("CREATE TABLE `" . db_prefix() . "lead_manager_zoom_meeting_access_token` (
                `id` int(11) NOT NULL,
                `staff_id` int(11) NOT NULL,
                `access_token` longtext NOT NULL,
                `token_type` varchar(50) NOT NULL,
                `expires_in` datetime NOT NULL,
                `scope` longtext NOT NULL,
                `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
                `counter` int(11) NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }
}
