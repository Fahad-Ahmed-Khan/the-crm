<?php defined('BASEPATH') or exit('No direct script access allowed');

$table_data = array(
    _l('purchase_order_dt_table_heading_number'),
    _l('purchase_order_dt_table_heading_amount'),
    _l('purchase_orders_total_tax'),
    array(
        'name' => _l('invoice_purchase_order_year'),
        'th_attrs' => array('class' => 'not_visible')
    ),
    array(
        'name' => _l('purchase_order_dt_table_heading_client'),
        'th_attrs' => array('class' => (isset($client) ? 'not_visible' : ''))
    ),
    _l('project'),
    _l('tags'),
    _l('purchase_order_dt_table_heading_date'),
    _l('reference_no'),
    _l('purchase_order_dt_table_heading_status'),
    _l('purchase_order_dt_table_heading_created_by')
);

$custom_fields = get_custom_fields('purchase_order', array('show_on_table' => 1));

foreach ($custom_fields as $field) {
    array_push($table_data, [
        'name' => $field['name'],
        'th_attrs' => array('data-type' => $field['type'], 'data-custom-field' => 1),
    ]);
}

$table_data = hooks()->apply_filters('purchase_orders_table_columns', $table_data);

render_datatable(
    $table_data,
    isset($class) ? $class : 'purchase_orders',
    [],
    [
        'id' => $table_id ?? 'purchase_orders',
        'data-default-order' => get_table_last_order('purchase_orders'),
        'data-last-order-identifier' => 'purchase_orders',
    ]
);