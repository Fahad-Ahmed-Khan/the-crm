<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Purchase Order
Description: A dedicated purchase order (PO) module for sales. It allows you to create purchase order. Convert Estimates to PO or create one. PO can be converted to DVL and Invoice.
Version: 1.0.8
Requires at least: 3.0.*
Author: ulutfa
Author URI: https://codecanyon.net/user/ulutfa
*/

defined('PURCHASE_ORDER_MODULE_NAME') or define('PURCHASE_ORDER_MODULE_NAME', 'purchase_orders');

$CI = &get_instance();

/**
 * Load the helpers
 */
$CI->load->helper(PURCHASE_ORDER_MODULE_NAME . '/' . PURCHASE_ORDER_MODULE_NAME);


/**
 * Load the models
 */
$CI->load->model(PURCHASE_ORDER_MODULE_NAME . '/' . PURCHASE_ORDER_MODULE_NAME . '_model');

/**
 * Register language files, must be registered if the module is using languages
 */
register_language_files(PURCHASE_ORDER_MODULE_NAME, [PURCHASE_ORDER_MODULE_NAME]);

/**
 * Register activation module hook
 */
register_activation_hook(PURCHASE_ORDER_MODULE_NAME, function () {
    require(__DIR__ . '/install.php');
});

/**
 * Register merge fields
 */
register_merge_fields(PURCHASE_ORDER_MODULE_NAME . '/merge_fields/purchase_order_merge_fields');
register_merge_fields(PURCHASE_ORDER_MODULE_NAME . '/merge_fields/editor_staff_merge_fields');
hooks()->add_filter('available_merge_fields', 'purchase_order_allow_staff_client_merge_fields');


/**
 * Init module menu items in setup in admin_init hook
 * @return null
 */
hooks()->add_action('admin_init', function () use ($CI) {
    // Register menu
    if (staff_can('view_own', PURCHASE_ORDER_MODULE_NAME) || staff_can('view', PURCHASE_ORDER_MODULE_NAME)) {
        $CI->app_menu->add_sidebar_children_item('sales', [
            'slug' => PURCHASE_ORDER_MODULE_NAME,
            'name' => _l(PURCHASE_ORDER_MODULE_NAME),
            'icon' => '',
            'href' => admin_url('purchase_orders'),
            'position' => 10,
        ]);
    }

    // Register permssion
    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
        'view_own' => _l('permission_view_own'),
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];
    register_staff_capabilities('purchase_orders', $capabilities, _l(PURCHASE_ORDER_MODULE_NAME));
}, PHP_INT_MAX);

/**
 * Register admin footer hook
 */
hooks()->add_action('app_admin_footer', function () {
    //load common admin asset
    $CI = &get_instance();
    $CI->load->view(PURCHASE_ORDER_MODULE_NAME . '/admin/scripts/common');
});

/**
 * Add settings to finance setting group
 */
hooks()->add_action('after_finance_settings_last_tab', function () {
    echo '<li role="presentation">
    <a href="#purchase_orders" aria-controls="purchase_orders" role="tab"
        data-toggle="tab">' . _l('purchase_orders') . '</a>
</li>';
});
hooks()->add_action('after_finance_settings_tabs_content', function () {
    get_instance()->load->view(PURCHASE_ORDER_MODULE_NAME . '/admin/' . PURCHASE_ORDER_MODULE_NAME . '/settings');
});


/**
 * CRUD and relation hooks
 */
hooks()->add_filter('before_purchase_order_added', '_format_data_sales_feature');
hooks()->add_filter('before_purchase_order_updated', '_format_data_sales_feature');

// Global search result query filter for Purchase Order Items
hooks()->add_filter('global_search_result_query', 'purchase_order_global_search_result_query', 10, 3);

// Task modal rel_type_select action
hooks()->add_action('task_modal_rel_type_select', 'purchase_order_task_modal_rel_type_select');

// Relation values filter
hooks()->add_filter('relation_values', 'purchase_order_relation_values', 10, 2);

// Get relation data filter
hooks()->add_filter('get_relation_data', 'purchase_order_get_relation_data', 10, 4);

// Tasks table row data filter
hooks()->add_filter('tasks_table_row_data', 'purchase_order_tasks_table_row_data', 10, 3);


/** Javascript helpers */
// Hooks to add menu item to convert estimate to PO. 
hooks()->add_action('after_admin_estimate_preview_template_tab_content_last_item', function ($estimate) {
    $CI = &get_instance();
    $CI->load->view(PURCHASE_ORDER_MODULE_NAME . '/admin/scripts/convert_from_estimate', ['estimate' => $estimate]);
});
// Only use this hard way of injection for old version of perfex that does not have neccessary hooks i.e 'after_admin_estimate_preview_template_tab_content_last_item'.
hooks()->add_action('admin_init', function () {
    if (config_item('migration_version') < 310) {
        $uri = uri_string();
        if (stripos($uri, 'estimates/get_estimate_data_ajax') !== false) {
            $estimate_id = explode('?', end(explode('/', $uri)))[0];
            $CI = &get_instance();
            $CI->load->model('estimates_model');
            $estimate = $CI->estimates_model->get($estimate_id);
            $CI->load->view(PURCHASE_ORDER_MODULE_NAME . '/admin/scripts/convert_from_estimate', ['estimate' => $estimate]);
        }
    }
});

/** Helpers to convert from invoice */
hooks()->add_action('after_admin_invoice_preview_template_tab_content_last_item', function ($invoice) {
    $CI = &get_instance();
    $CI->load->view(PURCHASE_ORDER_MODULE_NAME . '/admin/scripts/show_invoice_purchase_order', ['invoice' => $invoice]);
});


/**
 * Show PO on client profile and project tab
 */
hooks()->add_action('admin_init', function ()  use ($CI) {

    // Show on customer profile tab with badge
    $CI->app_tabs->add_customer_profile_tab(PURCHASE_ORDER_MODULE_NAME, [
        'name'     => _l(PURCHASE_ORDER_MODULE_NAME),
        'icon'     => 'fa-regular fa-file-lines',
        'view'     => 'purchase_orders/admin/purchase_orders/groups/client',
        'position' => 46,
        'badge'    => [],
    ]);
    hooks()->add_filter('customers_profile_tab_badge', function ($data) use ($CI) {
        if ($data['feature'] === PURCHASE_ORDER_MODULE_NAME) {
            $customerid = $data['customer_id'];

            if (staff_cant('view', 'purchase_orders')) {
                $where = get_purchase_orders_where_sql_for_staff(get_staff_user_id());
                $CI->db->where($where);
            }

            $count = $CI->db->where_not_in('status', [3, 4])->where('clientid', $customerid)->count_all_results('purchase_orders');
            if ($count > 0) {
                $badge = [
                    'value' => $count,
                    'color' => '',
                    'type'  => 'default',
                ];
                $data['badge'] = $badge;
            }
        }
        return $data;
    });

    // Show on project tab
    $CI->app_tabs->add_project_tab_children_item('sales', [
        'slug'     => PURCHASE_ORDER_MODULE_NAME,
        'name'     => _l(PURCHASE_ORDER_MODULE_NAME),
        'view'     => 'purchase_orders/admin/purchase_orders/groups/project',
        'position' => 11,
        'visible'  => (staff_can('view',  PURCHASE_ORDER_MODULE_NAME) || staff_can('view_own',  PURCHASE_ORDER_MODULE_NAME) || (get_option('allow_staff_view_' . PURCHASE_ORDER_MODULE_NAME . '_assigned') == 1 && staff_has_assigned_purchase_orders())),
    ]);
});

// Display email list on email template list
hooks()->add_action('after_email_templates', function () use ($CI) {
    $type = 'purchase_order';
    $CI->load->model('emails_model');
    $CI->load->view('purchase_orders/admin/email_templates', ['title' => _l(PURCHASE_ORDER_MODULE_NAME), 'templates' => $CI->emails_model->get(['type' => $type, 'language' => 'english']), 'email_type' => $type]);
});

// Add delivery note to customer portal
hooks()->add_action('clients_init', function () {
    if (is_client_logged_in()) {
        if (has_contact_permission(PURCHASE_ORDER_MODULE_NAME))
            add_theme_menu_item(PURCHASE_ORDER_MODULE_NAME, [
                'name' =>  _l(PURCHASE_ORDER_MODULE_NAME . '_client_menu'),
                'href' => base_url(PURCHASE_ORDER_MODULE_NAME . '/client'),
                'position' => 10,
            ]);
    }
});

hooks()->add_filter('get_contact_permissions', function ($permissions) {
    $permissions[] = [
        'id'         => PURCHASE_ORDER_MODULE_NAME,
        'name'       => _l('customer_permission_purchase_orders'),
        'short_name' => PURCHASE_ORDER_MODULE_NAME,
    ];
    return $permissions;
});

hooks()->add_action('after_customers_area_project_overview_tab', function ($project) {
    if (has_contact_permission(PURCHASE_ORDER_MODULE_NAME) && ($project->settings->available_features[PURCHASE_ORDER_MODULE_NAME] ?? 1) == 1) {
        echo '
        <li role="presentation" class="project_tab_' . PURCHASE_ORDER_MODULE_NAME . '">
            <a data-group="purchase_orders"
                href="' . site_url('purchase_orders/client/project/' . $project->id . '?group=' . PURCHASE_ORDER_MODULE_NAME) . '"
                role="tab">
                <i class="fa fa-file-invoice-dollar menu-icon" aria-hidden="true"></i>
                ' . _l(PURCHASE_ORDER_MODULE_NAME . '_client_menu') . '
            </a>
        </li>';
        echo '<script>document.addEventListener("DOMContentLoaded",()=>{$(".project_tab_purchase_orders").insertAfter(".project_tab_estimates");if(window.location.search.includes("purchase_orders"))$(".project_tab_purchase_orders")[0].scrollIntoView();});</script>';
    }
});

// Add pdf config 
hooks()->add_action('after_pdf_signature_settings_fields', function () {
    echo '<div class="purchase-order">';
    render_yes_no_option('show_pdf_signature_purchase_order', 'show_pdf_signature_purchase_order');
    echo '<hr /></div>';
    echo '<script>window.addEventListener("DOMContentLoaded", ()=>{$(".purchase-order").insertBefore($(".tab-pane#signature>.form-group").slice(-1)); });</script>';
});

// Allow customization of items column
hooks()->add_filter('custom_sales_resources_type_list', function ($resources) {
    $resources[] = 'purchase_order';
    return $resources;
});

// Custom fields dropdown options
hooks()->add_action('after_custom_fields_select_options', 'purchase_order_custom_filed_select_option');

// Exlude not importable columns
hooks()->add_filter('not_importable_clients_fields', function ($fields) {
    $fields[] = 'purchase_order_emails';
    return $fields;
});

// Dashboard widget
hooks()->add_action('after_dashboard_top_container', function () {
    $CI = &get_instance();
    $CI->load->view(PURCHASE_ORDER_MODULE_NAME . '/admin/scripts/dashboard_widget');
});
