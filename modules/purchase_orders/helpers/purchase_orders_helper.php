<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Get purchase_order short_url
 * @since  Version 2.7.3
 * @param  object $purchase_order
 * @return string Url
 */
function get_purchase_order_shortlink($purchase_order)
{
    $long_url = site_url("purchase_orders/client/po/{$purchase_order->id}/{$purchase_order->hash}");
    if (!get_option('bitly_access_token')) {
        return $long_url;
    }

    // Check if purchase_order has short link, if yes return short link
    if (!empty($purchase_order->short_link)) {
        return $purchase_order->short_link;
    }

    // Create short link and return the newly created short link
    $short_link = app_generate_short_link([
        'long_url' => $long_url,
        'title'    => format_purchase_order_number($purchase_order->id),
    ]);

    if ($short_link) {
        $CI = &get_instance();
        $CI->db->where('id', $purchase_order->id);
        $CI->db->update(db_prefix() . 'purchase_orders', [
            'short_link' => $short_link,
        ]);

        return $short_link;
    }

    return $long_url;
}

/**
 * Check purchase_order restrictions - hash, clientid
 * @param  mixed $id   purchase_order id
 * @param  string $hash purchase_order hash
 */
function check_purchase_order_restrictions($id, $hash)
{
    $CI = &get_instance();
    $CI->load->model('purchase_orders_model');
    if (!$hash || !$id) {
        show_404();
    }
    if (!is_client_logged_in() && !is_staff_logged_in()) {
        if (get_option('view_purchase_order_only_logged_in') == 1) {
            redirect_after_login_to_current_url();
            redirect(site_url('authentication/login'));
        }
    }
    $purchase_order = $CI->purchase_orders_model->get($id);
    if (!$purchase_order || ($purchase_order->hash != $hash)) {
        show_404();
    }
    // Do one more check
    if (!is_staff_logged_in()) {
        if (get_option('view_purchase_order_only_logged_in') == 1) {
            if ($purchase_order->clientid != get_client_user_id()) {
                show_404();
            }
        }
    }
}

/**
 * Check if purchase_order email template for expiry reminders is enabled
 * @return boolean
 */
function is_purchase_orders_email_expiry_reminder_enabled()
{
    return total_rows(db_prefix() . 'emailtemplates', ['slug' => 'purchase_order-expiry-reminder', 'active' => 1]) > 0;
}

/**
 * Check if there are sources for sending purchase_order expiry reminders
 * Will be either email or SMS
 * @return boolean
 */
function is_purchase_orders_expiry_reminders_enabled()
{
    return is_purchase_orders_email_expiry_reminder_enabled();
}

/**
 * Return RGBa purchase_order status color for PDF documents
 * @param  mixed $status_id current purchase_order status
 * @return string
 */
function purchase_order_status_color_pdf($status_id)
{
    if ($status_id == 1) {
        $statusColor = '119, 119, 119';
    } elseif ($status_id == 2) {
        // Sent
        $statusColor = '3, 169, 244';
    } elseif ($status_id == 3) {
        //Declines
        $statusColor = '252, 45, 66';
    } elseif ($status_id == 4) {
        //Accepted
        $statusColor = '0, 191, 54';
    } else {
        // Expired
        $statusColor = '255, 111, 0';
    }

    return hooks()->apply_filters('purchase_order_status_pdf_color', $statusColor, $status_id);
}

/**
 * Format purchase_order status
 * @param  integer  $status
 * @param  string  $classes additional classes
 * @param  boolean $label   To include in html label or not
 * @return mixed
 */
function format_purchase_order_status($status, $classes = '', $label = true)
{
    $id          = $status;
    $label_class = purchase_order_status_color_class($status);
    $status      = purchase_order_status_by_id($status);
    if ($label == true) {
        return '<span class="label label-' . $label_class . ' ' . $classes . ' s-status purchase_order-status-' . $id . ' purchase_order-status-' . $label_class . '">' . $status . '</span>';
    }

    return $status;
}

/**
 * Return purchase_order status translated by passed status id
 * @param  mixed $id purchase_order status id
 * @return string
 */
function purchase_order_status_by_id($id)
{
    $status = '';
    if ($id == 1) {
        $status = _l('purchase_order_status_new');
    } elseif ($id == 2) {
        $status = _l('purchase_order_status_sent');
    } elseif ($id == 4) {
        $status = _l('purchase_order_status_confirmed');
    } elseif ($id == 3) {
        $status = _l('purchase_order_status_cancelled');
    } else {
        if (!is_numeric($id)) {
            if ($id == 'not_sent') {
                $status = _l('not_sent_indicator');
            }
        }
    }

    return hooks()->apply_filters('purchase_order_status_label', $status, $id);
}

/**
 * Return purchase_order status color class based on twitter bootstrap
 * @param  mixed  $id
 * @param  boolean $replace_default_by_muted
 * @return string
 */
function purchase_order_status_color_class($id, $replace_default_by_muted = false)
{
    $class = '';
    if ($id == 1) {
        $class = 'default';
        if ($replace_default_by_muted == true) {
            $class = 'muted';
        }
    } elseif ($id == 2) {
        $class = 'info';
    } elseif ($id == 3) {
        $class = 'danger';
    } elseif ($id == 4) {
        $class = 'success';
    } elseif ($id == 5) {
        // status 5
        $class = 'warning';
    } else {
        if (!is_numeric($id)) {
            if ($id == 'not_sent') {
                $class = 'default';
                if ($replace_default_by_muted == true) {
                    $class = 'muted';
                }
            }
        }
    }

    return hooks()->apply_filters('purchase_order_status_color_class', $class, $id);
}

/**
 * Check if the purchase_order id is last invoice
 * @param  mixed  $id purchase_orderid
 * @return boolean
 */
function is_last_purchase_order($id)
{
    $CI = &get_instance();
    $CI->db->select('id')->from(db_prefix() . 'purchase_orders')->order_by('id', 'desc')->limit(1);
    $query            = $CI->db->get();
    $last_purchase_orderid = $query->row()->id;
    if ($last_purchase_orderid == $id) {
        return true;
    }

    return false;
}

/**
 * Format purchase_order number based on description
 * @param  mixed $id
 * @return string
 */
function format_purchase_order_number($id)
{
    $CI = &get_instance();

    if (!is_object($id)) {
        $CI->db->select('date,number,prefix,number_format')->from(db_prefix() . 'purchase_orders')->where('id', $id);
        $purchase_order = $CI->db->get()->row();
    } else {
        $purchase_order = $id;
        $id       = $purchase_order->id;
    }

    if (!$purchase_order) {
        return '';
    }

    $number = sales_number_format($purchase_order->number, $purchase_order->number_format, $purchase_order->prefix, $purchase_order->date);

    return hooks()->apply_filters('format_purchase_order_number', $number, [
        'id'       => $id,
        'purchase_order' => $purchase_order,
    ]);
}


/**
 * Function that return purchase_order item taxes based on passed item id
 * @param  mixed $itemid
 * @return array
 */
function get_purchase_order_item_taxes($itemid)
{
    $CI = &get_instance();
    $CI->db->where('itemid', $itemid);
    $CI->db->where('rel_type', 'purchase_order');
    $taxes = $CI->db->get(db_prefix() . 'item_tax')->result_array();
    $i     = 0;
    foreach ($taxes as $tax) {
        $taxes[$i]['taxname'] = $tax['taxname'] . '|' . $tax['taxrate'];
        $i++;
    }

    return $taxes;
}

/**
 * Calculate purchase_orders percent by status
 * @param  mixed $status          purchase_order status
 * @return array
 */
function get_purchase_orders_percent_by_status($status, $project_id = null)
{
    $has_permission_view = staff_can('view',  'purchase_orders');
    $where               = '';

    if (isset($project_id)) {
        $where .= 'project_id=' . get_instance()->db->escape_str($project_id) . ' AND ';
    }
    if (!$has_permission_view) {
        $where .= get_purchase_orders_where_sql_for_staff(get_staff_user_id());
    }

    $where = trim($where);

    if (endsWith($where, ' AND')) {
        $where = substr_replace($where, '', -3);
    }

    $total_purchase_orders = total_rows(db_prefix() . 'purchase_orders', $where);

    $data            = [];
    $total_by_status = 0;

    if (!is_numeric($status)) {
        if ($status == 'not_sent') {
            $total_by_status = total_rows(db_prefix() . 'purchase_orders', 'sent=0 AND status NOT IN(2,3,4)' . ($where != '' ? ' AND (' . $where . ')' : ''));
        }
    } else {
        $whereByStatus = 'status=' . $status;
        if ($where != '') {
            $whereByStatus .= ' AND (' . $where . ')';
        }
        $total_by_status = total_rows(db_prefix() . 'purchase_orders', $whereByStatus);
    }

    $percent                 = ($total_purchase_orders > 0 ? number_format(($total_by_status * 100) / $total_purchase_orders, 2) : 0);
    $data['total_by_status'] = $total_by_status;
    $data['percent']         = $percent;
    $data['total']           = $total_purchase_orders;

    return $data;
}

function get_purchase_orders_where_sql_for_staff($staff_id)
{
    $CI                                  = &get_instance();
    $has_permission_view_own             = staff_can('view_own',  'purchase_orders');
    $allow_staff_view_purchase_orders_assigned = get_option('allow_staff_view_purchase_orders_assigned');
    $whereUser                           = '';
    if ($has_permission_view_own) {
        $whereUser = '((' . db_prefix() . 'purchase_orders.addedfrom=' . $CI->db->escape_str($staff_id) . ' AND ' . db_prefix() . 'purchase_orders.addedfrom IN (SELECT staff_id FROM ' . db_prefix() . 'staff_permissions WHERE feature = "purchase_orders" AND capability="view_own"))';
        if ($allow_staff_view_purchase_orders_assigned == 1) {
            $whereUser .= ' OR sale_agent=' . $CI->db->escape_str($staff_id);
        }
        $whereUser .= ')';
    } else {
        $whereUser .= 'sale_agent=' . $CI->db->escape_str($staff_id);
    }

    return $whereUser;
}
/**
 * Check if staff member have assigned purchase_orders / added as sale agent
 * @param  mixed $staff_id staff id to check
 * @return boolean
 */
function staff_has_assigned_purchase_orders($staff_id = '')
{
    $CI       = &get_instance();
    $staff_id = is_numeric($staff_id) ? $staff_id : get_staff_user_id();
    $cache    = $CI->app_object_cache->get('staff-total-assigned-purchase_orders-' . $staff_id);

    if (is_numeric($cache)) {
        $result = $cache;
    } else {
        $result = total_rows(db_prefix() . 'purchase_orders', ['sale_agent' => $staff_id]);
        $CI->app_object_cache->add('staff-total-assigned-purchase_orders-' . $staff_id, $result);
    }

    return $result > 0 ? true : false;
}
/**
 * Check if staff member can view purchase_order
 * @param  mixed $id purchase_order id
 * @param  mixed $staff_id
 * @return boolean
 */
function user_can_view_purchase_order($id, $staff_id = false)
{
    $CI = &get_instance();

    $staff_id = $staff_id ? $staff_id : get_staff_user_id();

    if (has_permission('purchase_orders', $staff_id, 'view')) {
        return true;
    }

    $CI->db->select('id, addedfrom, sale_agent');
    $CI->db->from(db_prefix() . 'purchase_orders');
    $CI->db->where('id', $id);
    $purchase_order = $CI->db->get()->row();

    if ((has_permission('purchase_orders', $staff_id, 'view_own') && $purchase_order->addedfrom == $staff_id)
        || ($purchase_order->sale_agent == $staff_id && get_option('allow_staff_view_purchase_orders_assigned') == '1')
    ) {
        return true;
    }

    return false;
}

/**
 * Prepares email template preview $data for the view
 * @param  string $template    template class name
 * @param  mixed $customer_id_or_email customer ID to fetch the primary contact email or email
 * @return array
 */
if (!function_exists('my_prepare_mail_preview_data')) {
    function my_prepare_mail_preview_data($template, $customer_id_or_email, $mailClassParams = [])
    {
        $CI = &get_instance();

        if (is_numeric($customer_id_or_email)) {
            $contact = $CI->clients_model->get_contact(get_primary_contact_user_id($customer_id_or_email));
            $email   = $contact ? $contact->email : '';
        } else {
            $email = $customer_id_or_email;
        }

        $CI->load->model('emails_model');

        $data['template'] = $CI->app_mail_template->prepare($email, $template, $mailClassParams);
        $slug             = $CI->app_mail_template->get_default_property_value('slug', $template, $mailClassParams);

        $data['template_name'] = $slug;

        $template_result = $CI->emails_model->get(['slug' => $slug, 'language' => 'english'], 'row');

        $data['template_system_name'] = $template_result->name;
        $data['template_id']          = $template_result->emailtemplateid;

        $data['template_disabled'] = $template_result->active == 0;

        return $data;
    }
}

/**
 * Prepare general purchase order pdf
 * @since  Version 1.0.2
 * @param  object $purchase_order purchase order as object with all necessary fields
 * @param  string $tag tag for bulk pdf exporter
 * @return mixed object
 */
function purchase_order_pdf($purchase_order, $tag = '')
{
    return app_pdf('purchase_order', module_dir_path(PURCHASE_ORDER_MODULE_NAME, '/libraries/pdf/Purchase_order_pdf'), $purchase_order, $tag);
}


/**
 * HOOKS CALLBACKS
 */
/**
 * Global search result query for Purchase Order.
 *
 * @param array  $result Search result.
 * @param string $q      Search query.
 * @param int    $limit  Limit of results.
 *
 * @return array
 */
function purchase_order_global_search_result_query($result, $q, $limit)
{
    // Purchase Order Items Search
    $has_permission_view_purchase_orders = staff_can('view', 'purchase_orders');
    $has_permission_view_purchase_orders_own = staff_can('view_own', 'purchase_orders');
    $allow_staff_view_purchase_orders_assigned = get_option('allow_staff_view_purchase_orders_assigned');

    if ($has_permission_view_purchase_orders || $has_permission_view_purchase_orders_own || $allow_staff_view_purchase_orders_assigned) {
        $noPermissionQuery = get_purchase_orders_where_sql_for_staff(get_staff_user_id());

        $db = get_instance()->purchase_orders_model->db;
        $db->select()->from(db_prefix() . 'itemable');
        $db->where('rel_type', 'purchase_order');

        if (!$has_permission_view_purchase_orders) {
            $db->where('rel_id IN (select id from ' . db_prefix() . 'purchase_orders where ' . $noPermissionQuery . ')');
        }

        $db->where('(description LIKE "%' . $db->escape_like_str($q) . '%" ESCAPE \'!\' OR long_description LIKE "%' . $db->escape_like_str($q) . '%" ESCAPE \'!\')');
        $db->order_by('description', 'ASC');

        $result[] = [
            'result'         => $db->get()->result_array(),
            'type'           => 'purchase_order_items',
            'search_heading' => _l('purchase_order_items'),
        ];
    }

    return $result;
}

/**
 * Task modal relation type select for Purchase Order.
 *
 * @param array $value Selected value.
 *
 * @return void
 */
function purchase_order_task_modal_rel_type_select($value)
{
    $selected = '';

    if (isset($value) && isset($value['rel_type']) && $value['rel_type'] == 'purchase_order') {
        $selected = 'selected';
    }

    echo "<option value='purchase_order' " . $selected . ">" .
        _l('purchase_order') . "
           </option>";
}

/**
 * Relation values for Purchase Order.
 *
 * @param array $values Relation values.
 *
 * @return array
 */
function purchase_order_relation_values($values)
{
    $relation = $values['relation'] ?? [];

    if ($values['type'] == 'purchase_order') {
        if (empty($relation))
            $relation = ['id' => get_instance()->input->get('rel_id', true)];

        if (is_array($relation)) {
            $values['id'] = $relation['id'];
            $values['name'] = format_purchase_order_number($relation['id']);
        } else {
            $values['id'] = $relation->id;
            $values['name'] = format_purchase_order_number($relation->id);
        }

        $values['link'] = admin_url('purchase_orders/index/' . $values['id']);
    }

    return $values;
}

/**
 * Get relation data for Purchase Order.
 *
 * @param array $data Data for the relation.
 * @param array $obj  Object containing type and rel_id.
 *
 * @return array
 */
function purchase_order_get_relation_data($data, $obj)
{
    $type = $obj['type'];
    $rel_id = $obj['rel_id'];

    if ($type == 'purchase_order') {
        $CI = &get_instance();

        if ($rel_id != '') {
            $data = $CI->purchase_orders_model->get($rel_id);
        } else {
            $data = [];

            if ($CI->input->post('q')) {
                $table = db_prefix() . 'purchase_orders';
                $q = $CI->input->post('q');
                $q = trim($q);
                $CI->purchase_orders_model->db->or_where("$table.number LIKE '%$q%'");
                $CI->purchase_orders_model->db->or_where("$table.id LIKE '%$q%'");
                $data = $CI->purchase_orders_model->get();
            }
        }
    }

    return $data;
}

/**
 * Tasks table row data for Purchase Order.
 *
 * @param array $row  Current row data.
 * @param array $aRow All row data.
 *
 * @return array
 */
function purchase_order_tasks_table_row_data($row, $aRow)
{
    if ($aRow['rel_type'] == 'purchase_order') {

        $CI = &get_instance();
        $CI->load->model('purchase_orders/purchase_orders_model');

        $po = $CI->purchase_orders_model->get($aRow['rel_id']);

        if ($po) {
            $str = '<span class="hide"> - </span><a class="text-muted task-table-related" data-toggle="tooltip" title="' . _l('task_related_to') . '" href="' . admin_url('purchase_orders/purchase_order/' . $po->id) . '">' . format_purchase_order_number($po->id) . '</a><br />';

            $row[2] = $row[2] . $str;
        }
    }

    return $row;
}

/**
 * Get staff and contact fields and insert into email templates.
 *
 * @param [array] $fields
 *
 * @return array
 */
function purchase_order_allow_staff_client_merge_fields($fields)
{
    $done = 0;
    foreach ($fields as $index => $group) {
        foreach ($group as $key => $groupFields) {
            if ($key == 'client') {
                foreach ($groupFields as $groupIndex => $groupField) {
                    if (isset($groupField['templates']))
                        $fields[$index][$key][$groupIndex]['templates'] = array_merge($groupField['templates'], ['purchase-order-send-to-client']);
                }
                $done++;
                break;
            }
            if ($key == 'staff') {
                foreach ($groupFields as $groupIndex => $groupField) {
                    if (isset($groupField['templates']) && in_array($groupField['key'], ['{staff_firstname}', '{staff_lastname}', '{staff_email}']))

                        $fields[$index][$key][$groupIndex]['templates'] = array_merge($groupField['templates'], ['purchase-order-confirmed-to-staff', 'purchase-order-cancelled-to-staff']);
                }
                $done++;
                break;
            }
            if ($done > 1) break;
        }
    }

    return $fields;
}


/****************************START POLYFILL FOR LATEST PERFEX VESION*****************************/

if (!function_exists('staff_can')) {
    /**
     * Check if the logged in staff have the ability to perform given action on the resources
     * 
     * Polyfil method for those using older version of Perfex. 
     * @todo  Should be removed in future version
     *
     * @param string $action
     * @param string $resources
     * @return bool
     */
    function staff_can($action, $resources)
    {
        return has_permission($resources, '', $action);
    }
}

if (!function_exists('staff_cant')) {
    /**
     * Check if the logged in staff have the ability to perform given action on the resources
     * 
     * Polyfil method for those using older version of Perfex. 
     * @todo  Should be removed in future version
     *
     * @param string $action
     * @param string $resources
     * @return bool
     */
    function staff_cant($action, $resources)
    {
        return !staff_can($action, $resources);
    }
}

if (!function_exists('e')) {
    /**
     * Encode HTML special characters in a string.
     *
     * @param  bool  $doubleEncode
     * @return string
     */
    function e($value, $doubleEncode = true)
    {
        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', $doubleEncode);
    }
}
/****************************END POLYFILL FOR LATEST PERFEX VESION*****************************/

/**
 * Add purchase order to custom field relation select
 *
 * @param object|null $selected
 * @return void
 */
function purchase_order_custom_filed_select_option($selected)
{
    $data_ppt = '';
    if (is_object($selected)) {

        foreach ($selected as $key => $value) {

            if (in_array($key, ['show_on_table', 'show_on_pdf', 'show_on_client_portal']))
                $data_ppt .= "data-$key='$value' ";
        }
    }

    $selected = is_object($selected) ? $selected->fieldto : $selected;
    get_instance()->app_scripts->add('purchase-order-custom-field', module_dir_url(PURCHASE_ORDER_MODULE_NAME, 'assets/js/custom-field.js'));

    echo "<option $data_ppt value='purchase_order' " . ($selected == 'purchase_order' ? 'selected' : '') . ">" . _l(PURCHASE_ORDER_MODULE_NAME) . "</option>";
}
