<?php

/*
Module Name: Flat Admin Theme
Description: Flat aesthetics for Perfex CRM
Version: 1.0
Author: Themesic Interactive
Author URI: https://themesic.com
Requires at least: 2.3.2
*/

defined('BASEPATH') or exit('No direct script access allowed');

hooks()->add_action('app_admin_head', 'admin_theme_head_component');
hooks()->add_action('app_admin_footer', 'flatadmintheme_footer_js__component');
hooks()->add_action('app_admin_authentication_head', 'admin_theme_staff_login');

/**
 * Staff login includes
 * @return stylesheet / script
 */
function admin_theme_staff_login()
{
    echo '<link href="' . base_url('modules/flatadmintheme/assets/css/staff-login.css') . '"  rel="stylesheet" type="text/css" >';
    echo '<link href="' . base_url('modules/flatadmintheme/assets/css/font-awesome.css') . '"  rel="stylesheet" type="text/css" >';
    echo '<script src="' . module_dir_url('flatadmintheme', 'assets/js/sign_in.js') . '"></script>';
}


/**
 * Injects theme's CSS
 * @return null
 */
function admin_theme_head_component()
{
    echo '<link href="' . base_url('modules/flatadmintheme/assets/css/fonts.css') . '" rel="stylesheet">';
    echo '<link href="' . base_url('modules/flatadmintheme/assets/css/main-style.css') . '"  rel="stylesheet" type="text/css" >';
    echo '<link href="' . base_url('modules/flatadmintheme/assets/css/animated.css') . '"  rel="stylesheet" type="text/css" >';
    echo '<script src="' . module_dir_url('flatadmintheme', 'assets/js/third-party/nanobar.js') . '"></script>';
    echo '<script src="' . module_dir_url('flatadmintheme', 'assets/js/third-party/waves076.min.js') . '"></script>';
}

/**
 * Injects theme's JS components in footer
 * @return null
 */
function flatadmintheme_footer_js__component()
{
    
    $CI = &get_instance();
	$viewuri = $_SERVER['REQUEST_URI'];
    if (strpos($viewuri, 'admin/projects/view') == false) {
        echo '<script src="' . module_dir_url('flatadmintheme', 'assets/js/admins.js') . '"></script>';
	}
	
}