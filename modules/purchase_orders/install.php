<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// Add options
$options = [
    'purchase_order_prefix' => 'PO-',
    'next_purchase_order_number' => 1,
    'delete_only_on_last_purchase_order' => '1',
    'purchase_order_number_decrement_on_delete' => '1',
    'allow_staff_view_purchase_orders_assigned' => '1',
    'view_purchase_order_only_logged_in' => '0',
    'show_sale_agent_on_purchase_orders' => '1',
    'show_project_on_purchase_order' => '1',
    'purchase_order_auto_convert_to_invoice_on_staff_confirm' => '0',
    'purchase_order_number_format' => 1,
    'purchase_orders_pipeline_limit' => '50',
    'default_purchase_orders_pipeline_sort' => 'pipeline_order',
    'default_purchase_orders_pipeline_sort_type' => 'asc',
    'predefined_clientnote_purchase_order' => '',
    'predefined_terms_purchase_order' => '',
    'purchase_order_allow_creating_from_estimate' => '1',
    'purchase_order_allow_convert_to_invoice' => '1',
    'show_pdf_signature_purchase_order' => '1',
    'show_purchase_order_status_widget_on_dashboard' => '1',
];

foreach ($options as $name => $default_value) {
    add_option($name, $default_value);
}

// Create tables
$table = db_prefix() . 'purchase_orders';
if (!$CI->db->table_exists($table)) {
    $CI->db->query(
        "CREATE TABLE IF NOT EXISTS `" . $table . "` (
            `id` int NOT NULL AUTO_INCREMENT,
            `sent` tinyint(1) NOT NULL DEFAULT '0',
            `datesend` datetime DEFAULT NULL,
            `clientid` int NOT NULL,
            `deleted_customer_name` varchar(100) DEFAULT NULL,
            `project_id` int NOT NULL DEFAULT '0',
            `number` int NOT NULL,
            `prefix` varchar(50) DEFAULT NULL,
            `number_format` int NOT NULL DEFAULT '0',
            `hash` varchar(32) DEFAULT NULL,
            `datecreated` datetime NOT NULL,
            `date` date NOT NULL,
            `currency` int NOT NULL,
            `subtotal` decimal(15,2) NOT NULL,
            `total_tax` decimal(15,2) NOT NULL DEFAULT '0.00',
            `total` decimal(15,2) NOT NULL,
            `adjustment` decimal(15,2) DEFAULT NULL,
            `addedfrom` int NOT NULL,
            `status` int NOT NULL DEFAULT '1',
            `clientnote` text,
            `adminnote` text,
            `discount_percent` decimal(15,2) DEFAULT '0.00',
            `discount_total` decimal(15,2) DEFAULT '0.00',
            `discount_type` varchar(30) DEFAULT NULL,
            `invoiceid` int DEFAULT NULL,
            `delivery_noteid` int DEFAULT NULL,
            `invoiced_date` datetime DEFAULT NULL,
            `terms` text,
            `reference_no` varchar(100) DEFAULT NULL,
            `sale_agent` int NOT NULL DEFAULT '0',
            `billing_street` varchar(200) DEFAULT NULL,
            `billing_city` varchar(100) DEFAULT NULL,
            `billing_state` varchar(100) DEFAULT NULL,
            `billing_zip` varchar(100) DEFAULT NULL,
            `billing_country` int DEFAULT NULL,
            `shipping_street` varchar(200) DEFAULT NULL,
            `shipping_city` varchar(100) DEFAULT NULL,
            `shipping_state` varchar(100) DEFAULT NULL,
            `shipping_zip` varchar(100) DEFAULT NULL,
            `shipping_country` int DEFAULT NULL,
            `include_shipping` tinyint(1) NOT NULL,
            `show_shipping_on_purchase_order` tinyint(1) NOT NULL DEFAULT '1',
            `show_quantity_as` int NOT NULL DEFAULT '1',
            `pipeline_order` int DEFAULT '1',
            `is_expiry_notified` int NOT NULL DEFAULT '0',
            `acceptance_firstname` varchar(50) DEFAULT NULL,
            `acceptance_lastname` varchar(50) DEFAULT NULL,
            `acceptance_email` varchar(100) DEFAULT NULL,
            `acceptance_date` datetime DEFAULT NULL,
            `acceptance_ip` varchar(40) DEFAULT NULL,
            `signature` varchar(40) DEFAULT NULL,
            `short_link` varchar(100) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `clientid` (`clientid`),
            KEY `currency` (`currency`),
            KEY `project_id` (`project_id`),
            KEY `sale_agent` (`sale_agent`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";"
    );

    // Update last project avialble features to include the module. This ensure new projects have delivery notes enabled by defautl.
    $CI->load->model('projects_model');
    $last_project_settings = $CI->projects_model->get_last_project_settings();
    if (count($last_project_settings)) {
        $key                                          = array_search('available_features', array_column($last_project_settings, 'name'));
        $last_project_settings[$key]['value'] = unserialize($last_project_settings[$key]['value']);
        $last_project_id = $last_project_settings[$key]['project_id'];
        $last_project_features = $last_project_settings[$key]['value'];
        if (!in_array('purchase_orders', $last_project_features)) {
            $last_project_features['purchase_orders'] = 1;
            $new_last_project_features = serialize($last_project_features);
            $CI->projects_model->db->where('name', 'available_features');
            $CI->projects_model->db->where('project_id', $last_project_id);
            $CI->projects_model->db->update(db_prefix() . 'project_settings', ['value' => $new_last_project_features]);
        }
    }
}

if (!$CI->db->field_exists('created_by', $table)) {
    $CI->db->query("ALTER TABLE `$table` ADD `created_by` int DEFAULT NULL");
}

$table = db_prefix() . 'contacts';
if (!$CI->db->field_exists('purchase_order_emails', $table)) {
    $CI->db->query("ALTER TABLE `$table` ADD `purchase_order_emails` tinyint(1) NOT NULL DEFAULT '1'");
}

$table = db_prefix() . 'estimates';
if (!$CI->db->field_exists('purchase_orderid', $table)) {
    $CI->db->query("ALTER TABLE `$table` ADD `purchase_orderid` int DEFAULT NULL");
}

$email_templates = [];
$email_templates[] = [
    'type' => 'purchase_order',
    'slug' => 'purchase-order-send-to-client',
    'name' => 'Send Purchase Order to Customer',
    'subject' => 'Purchase Order # {purchase_order_number}',
    'message' => '<span style="font-size: 12pt;">Dear {contact_firstname} {contact_lastname},</span><br /><br /><span style="font-size: 12pt;">Please find the attached purchase order <strong># {purchase_order_number}</strong></span><br /><br /><span style="font-size: 12pt;"><strong>Purchase Order status:</strong> {purchase_order_status}</span><br /><br /><span style="font-size: 12pt;">You can view the purchase order on the following link: <a href="{purchase_order_link}">{purchase_order_number}</a></span><br /><br /><span style="font-size: 12pt;">We look forward to your communication.</span><br /><br /><span style="font-size: 12pt;">Kind Regards,</span><br /><span style="font-size: 12pt;">{email_signature}<br /></span>'
];

$email_templates[] = [
    'type' => 'purchase_order',
    'slug' => 'purchase-order-cancelled-to-staff',
    'name' => 'Purchase Order Cancelled (Sent to Staff)',
    'subject' => 'Cancelled Purchase Order Notification',
    'message' => '<span style="font-size: 12pt;">Hello {staff_firstname} {staff_lastname},</span><br /><br /><span style="font-size: 12pt;">The purchase order with number <strong># {purchase_order_number}</strong> has been cancelled by {editor_staff_firstname} {editor_staff_lastname}</span><br /><br /><span style="font-size: 12pt;">You can view the details on the following link: <a href="{purchase_order_link}">{purchase_order_number}</a></span><br /><br /><span style="font-size: 12pt;">{email_signature}</span>',
];

$email_templates[] = [
    'type' => 'purchase_order',
    'slug' => 'purchase-order-confirmed-to-staff',
    'name' => 'Purchase Order Confirmed (Sent to Staff)',
    'subject' => 'Confirmed Purchase Order Notification',
    'message' => '<span style="font-size: 12pt;">Hello {staff_firstname} {staff_lastname},</span><br /><br /><span style="font-size: 12pt;">The purchase order with number <strong># {purchase_order_number}</strong> has been confirmed by {editor_staff_firstname} {editor_staff_lastname}</span><br /><br /><span style="font-size: 12pt;">You can view the details on the following link: <a href="{purchase_order_link}">{purchase_order_number}</a></span><br /><br /><span style="font-size: 12pt;">{email_signature}</span>',
];

$email_templates[] = [
    'type' => 'purchase_order',
    'slug' => 'purchase-order-status-updated-to-staff',
    'name' => 'Purchase Order Status Update (Sent to Staff)',
    'subject' => 'Purchase Order Status Update Notification',
    'message' => '<span style="font-size: 12pt;">Hello {staff_firstname} {staff_lastname},</span><br /><br /><span style="font-size: 12pt;">The purchase order with number <strong># {purchase_order_number}</strong> has been marked as <b>{purchase_order_status}</b> by {editor_staff_firstname} {editor_staff_lastname}</span><br /><br /><span style="font-size: 12pt;">You can view the details on the following link: <a href="{purchase_order_link}">{purchase_order_number}</a></span><br /><br /><span style="font-size: 12pt;">{email_signature}</span>',
];

$CI->load->model('emails_model');
$fromname = '{companyname} | CRM';
foreach ($email_templates as $t) {
    //this helper check buy slug and create if not exist by slug
    create_email_template($t['subject'], $t['message'], $t['type'], $t['name'], $t['slug']);
}

// Add delivery notes to default contact permission list
$default_contact_permissions =  unserialize(get_option('default_contact_permissions'));
if (!in_array('purchase_orders', $default_contact_permissions)) {
    $default_contact_permissions[] = 'purchase_orders';
    $default_contact_permissions = serialize($default_contact_permissions);
    update_option('default_contact_permissions', $default_contact_permissions);
}
