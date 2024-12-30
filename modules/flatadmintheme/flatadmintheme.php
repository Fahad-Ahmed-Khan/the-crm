<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Flat Admin Theme
Module URI: https://codecanyon.net/item/flat-admin-theme-for-perfex-crm/24754675
Description: Flat aesthetics for Perfex CRM's backend
Version: 1.0.4
Author: Themesic Interactive
Author URI: https://themesic.com
Requires at least: 2.3.2
*/

define('FLATADMINTHEME_MODULE', 'flatadmintheme');
define('FLAT_ADMIN_THEME_CSS', module_dir_path(FLATADMINTHEME_MODULE, 'assets/css/main-style.css'));
require_once __DIR__.'/vendor/autoload.php';
modules\flatadmintheme\core\Apiinit::the_da_vinci_code(FLATADMINTHEME_MODULE);
modules\flatadmintheme\core\Apiinit::ease_of_mind(FLATADMINTHEME_MODULE);
$CI = &get_instance();

/**
 * Register the activation chat
 */
register_activation_hook(FLATADMINTHEME_MODULE, 'flatadmintheme_activation_hook');

/**
 * The activation function
 */
function flatadmintheme_activation_hook()
{
	require(__DIR__ . '/install.php');
}

/**
 * Register chat language files
 */
register_language_files(FLATADMINTHEME_MODULE, ['flatadmintheme']);

/**
 * Load the chat helper
 */
$CI->load->helper(FLATADMINTHEME_MODULE . '/flatadmintheme');


hooks()->add_action('app_init', FLATADMINTHEME_MODULE.'_actLib');
function flatadmintheme_actLib()
{
    $CI = &get_instance();
    $CI->load->library(FLATADMINTHEME_MODULE.'/Flatadmintheme_aeiou');
//    $envato_res = $CI->flatadmintheme_aeiou->validatePurchase(FLATADMINTHEME_MODULE);
//    if (!$envato_res) {
//        set_alert('danger', 'One of your modules failed its verification and got deactivated. Please reactivate or contact support.');
//    }
}

hooks()->add_action('pre_activate_module', FLATADMINTHEME_MODULE.'_sidecheck');
function flatadmintheme_sidecheck($module_name)
{
    if (FLATADMINTHEME_MODULE == $module_name['system_name']) {
        modules\flatadmintheme\core\Apiinit::activate($module_name);
    }
}

hooks()->add_action('pre_deactivate_module', FLATADMINTHEME_MODULE.'_deregister');
function flatadmintheme_deregister($module_name)
{
    if (FLATADMINTHEME_MODULE == $module_name['system_name']) {
        delete_option(FLATADMINTHEME_MODULE.'_verification_id');
        delete_option(FLATADMINTHEME_MODULE.'_last_verification');
        delete_option(FLATADMINTHEME_MODULE.'_product_token');
        delete_option(FLATADMINTHEME_MODULE.'_heartbeat');
    }
}
