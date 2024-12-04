<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Styleflow
Description: Enhance the visual appeal of your invoices with the Styleflow module for Perfex CRM. This powerful extension empowers clients to customize and choose from a variety of professionally designed invoice templates, adding a personalized touch to their financial communications.
Version: 1.0.0
Author: LenzCreative
Author URI: https://codecanyon.net/user/lenzcreativee/portfolio
Requires at least: 1.0.*
*/

define('STYLEFLOW_MODULE_NAME', 'styleflow');

hooks()->add_action('admin_init', 'styleflow_module_init_menu_items');
hooks()->add_action('admin_init', 'styleflow_permissions');
	//Nulled	
//hooks()->add_action('styleflow_init', STYLEFLOW_MODULE_NAME . '_appint');
//hooks()->add_action('pre_activate_module', STYLEFLOW_MODULE_NAME . '_preactivate');
//hooks()->add_action('pre_deactivate_module', STYLEFLOW_MODULE_NAME . '_predeactivate');
//hooks()->add_action('pre_uninstall_module', STYLEFLOW_MODULE_NAME . '_uninstall');

/**
 * Load the module helper
 */
$CI = &get_instance();
$CI->load->helper(STYLEFLOW_MODULE_NAME . '/styleflow');

function styleflow_permissions()
{
    $capabilities = [];

    $capabilities['capabilities'] = [
        'view' => _l('permission_view'),
        'edit' => _l('permission_edit')
    ];
    register_staff_capabilities('styleflow', $capabilities, _l('styleflow'));
}

/**
 * Register activation module hook
 */
register_activation_hook(STYLEFLOW_MODULE_NAME, 'styleflow_module_activation_hook');

function styleflow_module_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

/**
 * Register language files, must be registered if the module is using languages
 */
register_language_files(STYLEFLOW_MODULE_NAME, [STYLEFLOW_MODULE_NAME]);

/**
 * Init module menu items in setup in admin_init hook
 * @return null
 */
function styleflow_module_init_menu_items()
{
    $CI = &get_instance();

    if (has_permission('styleflow', '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item('styleflow', [
            'slug' => 'styleflow',
            'name' => _l('styleflow'),
            'position' => 6,
            'icon' => 'fas fa-palette',
            'href' => admin_url('styleflow/manage_invoice_templates')
        ]);
    }
}

function styleflow_appint(){	//Nulled	
}
function styleflow_preactivate($module_name){	//Nulled	
}
function styleflow_predeactivate($module_name){	//Nulled	
}
function styleflow_uninstall($module_name){	//Nulled	
}