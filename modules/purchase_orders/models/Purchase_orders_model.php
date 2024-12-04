<?php

use app\services\AbstractKanban;

defined('BASEPATH') or exit('No direct script access allowed');

class Purchase_orders_model extends App_Model
{
    private $statuses;

    private $shipping_fields = ['shipping_street', 'shipping_city', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'];

    public function __construct()
    {
        parent::__construct();

        $this->statuses = hooks()->apply_filters('before_set_purchase_order_statuses', [
            1,
            //2,
            3,
            4
        ]);
    }

    /**
     * Get unique sale agent for purchase_orders / Used for filters
     * @return array
     */
    public function get_sale_agents()
    {
        return $this->db->query("SELECT DISTINCT(sale_agent) as sale_agent, CONCAT(firstname, ' ', lastname) as full_name FROM " . db_prefix() . 'purchase_orders JOIN ' . db_prefix() . 'staff on ' . db_prefix() . 'staff.staffid=' . db_prefix() . 'purchase_orders.sale_agent WHERE sale_agent != 0')->result_array();
    }

    /**
     * Get purchase_order/s
     * @param mixed $id purchase_order id
     * @param array $where perform where
     * @return mixed
     */
    public function get($id = '', $where = [])
    {
        $this->db->select('*,' . db_prefix() . 'currencies.id as currencyid, ' . db_prefix() . 'purchase_orders.id as id, ' . db_prefix() . 'currencies.name as currency_name');
        $this->db->from(db_prefix() . 'purchase_orders');
        $this->db->join(db_prefix() . 'currencies', db_prefix() . 'currencies.id = ' . db_prefix() . 'purchase_orders.currency', 'left');
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'purchase_orders.id', $id);
            $purchase_order = $this->db->get()->row();
            if ($purchase_order) {
                $purchase_order->attachments                           = $this->get_attachments($id);
                $purchase_order->visible_attachments_to_customer_found = false;

                foreach ($purchase_order->attachments as $attachment) {
                    if ($attachment['visible_to_customer'] == 1) {
                        $purchase_order->visible_attachments_to_customer_found = true;

                        break;
                    }
                }

                $purchase_order->items = get_items_by_type('purchase_order', $id);

                if ($purchase_order->project_id) {
                    $this->load->model('projects_model');
                    $purchase_order->project_data = $this->projects_model->get($purchase_order->project_id);
                }

                $purchase_order->client = $this->clients_model->get($purchase_order->clientid);

                if (!$purchase_order->client) {
                    $purchase_order->client          = new stdClass();
                    $purchase_order->client->company = $purchase_order->deleted_customer_name;
                }

                $this->load->model('email_schedule_model');
                $purchase_order->scheduled_email = $this->email_schedule_model->get($id, 'purchase_order');

                // Add estimate
                $estimate = $this->estimates_model->db->where('purchase_orderid', $id)->from(db_prefix() . 'estimates')->get()->row();
                if ($estimate) {
                    $purchase_order->estimate = $estimate;
                }
            }

            return $purchase_order;
        }
        $this->db->order_by('number,YEAR(date)', 'desc');

        return $this->db->get()->result_array();
    }

    /**
     * Get purchase_order statuses
     * @return array
     */
    public function get_statuses()
    {
        return $this->statuses;
    }

    /**
     * Convert purchase_order to invoice
     * @param mixed $id purchase_order id
     * @return mixed     New invoice ID
     */
    public function convert_to_invoice($id, $client = false, $draft_invoice = false)
    {
        // Recurring invoice date is okey lets convert it to new invoice
        $_purchase_order = $this->get($id);
        if (!empty($_purchase_order->invoiceid)) {
            if (!empty($this->invoices_model->get($_purchase_order->invoiceid)))
                return $_purchase_order->invoiceid;
        }

        $new_invoice_data = [];
        if ($draft_invoice == true) {
            $new_invoice_data['save_as_draft'] = true;
        }
        $new_invoice_data['clientid']   = $_purchase_order->clientid;
        $new_invoice_data['project_id'] = $_purchase_order->project_id;
        $new_invoice_data['number']     = get_option('next_invoice_number');
        $new_invoice_data['date']       = _d(date('Y-m-d'));
        $new_invoice_data['duedate']    = _d(date('Y-m-d'));
        if (get_option('invoice_due_after') != 0) {
            $new_invoice_data['duedate'] = _d(date('Y-m-d', strtotime('+' . get_option('invoice_due_after') . ' DAY', strtotime(date('Y-m-d')))));
        }
        $new_invoice_data['show_quantity_as'] = $_purchase_order->show_quantity_as;
        $new_invoice_data['currency']         = $_purchase_order->currency;
        $new_invoice_data['subtotal']         = $_purchase_order->subtotal;
        $new_invoice_data['total']            = $_purchase_order->total;
        $new_invoice_data['adjustment']       = $_purchase_order->adjustment;
        $new_invoice_data['discount_percent'] = $_purchase_order->discount_percent;
        $new_invoice_data['discount_total']   = $_purchase_order->discount_total;
        $new_invoice_data['discount_type']    = $_purchase_order->discount_type;
        $new_invoice_data['sale_agent']       = $_purchase_order->sale_agent;
        // Since version 1.0.6
        $new_invoice_data['billing_street']   = clear_textarea_breaks($_purchase_order->billing_street);
        $new_invoice_data['billing_city']     = $_purchase_order->billing_city;
        $new_invoice_data['billing_state']    = $_purchase_order->billing_state;
        $new_invoice_data['billing_zip']      = $_purchase_order->billing_zip;
        $new_invoice_data['billing_country']  = $_purchase_order->billing_country;
        $new_invoice_data['shipping_street']  = clear_textarea_breaks($_purchase_order->shipping_street);
        $new_invoice_data['shipping_city']    = $_purchase_order->shipping_city;
        $new_invoice_data['shipping_state']   = $_purchase_order->shipping_state;
        $new_invoice_data['shipping_zip']     = $_purchase_order->shipping_zip;
        $new_invoice_data['shipping_country'] = $_purchase_order->shipping_country;

        if ($_purchase_order->include_shipping == 1) {
            $new_invoice_data['include_shipping'] = 1;
        }

        $new_invoice_data['show_shipping_on_invoice'] = $_purchase_order->show_shipping_on_purchase_order;
        $new_invoice_data['terms']                    = get_option('predefined_terms_invoice');
        $new_invoice_data['clientnote']               = get_option('predefined_clientnote_invoice');
        // Set to unpaid status automatically
        $new_invoice_data['status']    = 1;
        $new_invoice_data['adminnote'] = '';

        $this->load->model('payment_modes_model');
        $modes = $this->payment_modes_model->get('', [
            'expenses_only !=' => 1,
        ]);
        $temp_modes = [];
        foreach ($modes as $mode) {
            if ($mode['selected_by_default'] == 0) {
                continue;
            }
            $temp_modes[] = $mode['id'];
        }
        $new_invoice_data['allowed_payment_modes'] = $temp_modes;
        $new_invoice_data['newitems']              = [];
        $custom_fields_items                       = get_custom_fields('items');
        $key                                       = 1;
        foreach ($_purchase_order->items as $item) {
            $new_invoice_data['newitems'][$key]['description']      = $item['description'];
            $new_invoice_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $new_invoice_data['newitems'][$key]['qty']              = $item['qty'];
            $new_invoice_data['newitems'][$key]['unit']             = $item['unit'];
            $new_invoice_data['newitems'][$key]['taxname']          = [];
            $taxes                                                  = get_purchase_order_item_taxes($item['id']);
            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($new_invoice_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $new_invoice_data['newitems'][$key]['rate']  = $item['rate'];
            $new_invoice_data['newitems'][$key]['order'] = $item['item_order'];
            foreach ($custom_fields_items as $cf) {
                $new_invoice_data['newitems'][$key]['custom_fields']['items'][$cf['id']] = get_custom_field_value($item['id'], $cf['id'], 'items', false);

                if (!defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
                    define('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST', true);
                }
            }
            $key++;
        }
        $this->load->model('invoices_model');
        $id = $this->invoices_model->add($new_invoice_data);
        if ($id) {
            // Customer accepted the purchase_order and is auto converted to invoice
            if (!is_staff_logged_in()) {
                $this->db->where('rel_type', 'invoice');
                $this->db->where('rel_id', $id);
                $this->db->delete(db_prefix() . 'sales_activity');
                $this->invoices_model->log_invoice_activity($id, 'invoice_activity_auto_converted_from_purchase_order', true, serialize([
                    '<a href="' . admin_url('purchase_orders/list_purchase_orders/' . $_purchase_order->id) . '">' . format_purchase_order_number($_purchase_order->id) . '</a>',
                ]));
            }
            // For all cases update addefrom and sale agent from the invoice
            // May happen staff is not logged in and these values to be 0
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'invoices', [
                'addedfrom'  => $_purchase_order->addedfrom,
                'sale_agent' => $_purchase_order->sale_agent,
            ]);

            // Update purchase_order with the new invoice data and set to status accepted
            $this->db->where('id', $_purchase_order->id);
            $this->db->update(db_prefix() . 'purchase_orders', [
                'invoiced_date' => date('Y-m-d H:i:s'),
                'invoiceid'     => $id,
                'status'        => 4,
            ]);

            $this->transfer_custom_fields('purchase_order', 'invoice', $_purchase_order->id, $id);

            if ($client == false) {
                $this->log_purchase_order_activity($_purchase_order->id, 'purchase_order_activity_converted', false, serialize([
                    '<a href="' . admin_url('invoices/list_invoices/' . $id) . '">' . format_invoice_number($id) . '</a>',
                ]));
            }

            hooks()->do_action('purchase_order_converted_to_invoice', ['invoice_id' => $id, 'purchase_orderid' => $_purchase_order->id]);
        }

        return $id;
    }

    public function convert_from_estimate($estimateid, $status)
    {

        $this->load->model('estimates_model');

        $_estimate = $this->estimates_model->get($estimateid);
        if (!empty($_estimate->purchase_orderid)) {
            if (!empty($this->get($_estimate->purchase_orderid)))
                return $_estimate->purchase_orderid;
        }

        $new_purchase_order_data               = [];
        $new_purchase_order_data['clientid']   = $_estimate->clientid;
        $new_purchase_order_data['project_id'] = $_estimate->project_id;
        $new_purchase_order_data['number']     = get_option('next_purchase_order_number');
        $new_purchase_order_data['date']       = _d(date('Y-m-d'));

        $new_purchase_order_data['show_quantity_as'] = $_estimate->show_quantity_as;
        $new_purchase_order_data['currency']         = $_estimate->currency;
        $new_purchase_order_data['subtotal']         = $_estimate->subtotal;
        $new_purchase_order_data['total']            = $_estimate->total;
        $new_purchase_order_data['adminnote']        = $_estimate->adminnote;
        $new_purchase_order_data['adjustment']       = $_estimate->adjustment;
        $new_purchase_order_data['discount_percent'] = $_estimate->discount_percent;
        $new_purchase_order_data['discount_total']   = $_estimate->discount_total;
        $new_purchase_order_data['discount_type']    = $_estimate->discount_type;
        $new_purchase_order_data['terms']            = $_estimate->terms;
        $new_purchase_order_data['sale_agent']       = $_estimate->sale_agent;
        $new_purchase_order_data['reference_no']     = $_estimate->reference_no;
        // Since version 1.0.6
        $new_purchase_order_data['billing_street']   = clear_textarea_breaks($_estimate->billing_street);
        $new_purchase_order_data['billing_city']     = $_estimate->billing_city;
        $new_purchase_order_data['billing_state']    = $_estimate->billing_state;
        $new_purchase_order_data['billing_zip']      = $_estimate->billing_zip;
        $new_purchase_order_data['billing_country']  = $_estimate->billing_country;
        $new_purchase_order_data['shipping_street']  = clear_textarea_breaks($_estimate->shipping_street);
        $new_purchase_order_data['shipping_city']    = $_estimate->shipping_city;
        $new_purchase_order_data['shipping_state']   = $_estimate->shipping_state;
        $new_purchase_order_data['shipping_zip']     = $_estimate->shipping_zip;
        $new_purchase_order_data['shipping_country'] = $_estimate->shipping_country;
        if ($_estimate->include_shipping == 1) {
            $new_purchase_order_data['include_shipping'] = $_estimate->include_shipping;
        }
        $new_purchase_order_data['show_shipping_on_purchase_order'] = $_estimate->show_shipping_on_estimate;
        // Set to unpaid status automatically
        $new_purchase_order_data['status']     = $status;
        $new_purchase_order_data['clientnote'] = $_estimate->clientnote;
        $new_purchase_order_data['adminnote']  = '';
        $new_purchase_order_data['newitems']   = [];
        $custom_fields_items             = get_custom_fields('items');
        $key                             = 1;
        foreach ($_estimate->items as $item) {
            $new_purchase_order_data['newitems'][$key]['description']      = $item['description'];
            $new_purchase_order_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $new_purchase_order_data['newitems'][$key]['qty']              = $item['qty'];
            $new_purchase_order_data['newitems'][$key]['unit']             = $item['unit'];
            $new_purchase_order_data['newitems'][$key]['taxname']          = [];
            $taxes                                                   = get_estimate_item_taxes($item['id']);
            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($new_purchase_order_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $new_purchase_order_data['newitems'][$key]['rate']  = $item['rate'];
            $new_purchase_order_data['newitems'][$key]['order'] = $item['item_order'];
            foreach ($custom_fields_items as $cf) {
                $new_purchase_order_data['newitems'][$key]['custom_fields']['items'][$cf['id']] = get_custom_field_value($item['id'], $cf['id'], 'items', false);

                if (!defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
                    define('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST', true);
                }
            }
            $key++;
        }
        $id = $this->add($new_purchase_order_data);

        if ($id) {

            // Update estimate with the new purchase order data
            $this->db->where('id', $_estimate->id);
            $this->db->update(db_prefix() . 'estimates', [
                'purchase_orderid'     => $id,
            ]);

            $tags = get_tags_in($_estimate->id, 'estimate');
            handle_tags_save($tags, $id, 'purchase_order');

            $this->transfer_custom_fields('estimate', 'purchase_order', $_estimate->id, $id);

            $this->estimates_model->log_estimate_activity($_estimate->id, 'estimate_activity_converted_to_purchase_order', false, serialize([
                '<a href="' . admin_url('purchase_orders/purchase_order/' . $id) . '">' . format_purchase_order_number($id) . '</a>',
            ]));

            hooks()->do_action('estimate_converted_to_purchase_order', ['purchase_orderid' => $id, 'estimate_id' => $_estimate->id]);

            return $id;
        }

        return false;
    }

    /**
     * Copy a resources (i.e estiamte, invoice ) custom field to another custom field (i.e purcahse order)
     * 
     * @param string $from_res i.e estimate
     * @param string $to_res i.e invoice
     * @param string|number $from_res_id The source resources id
     * @param string|number $to_res_id The target resources id
     * @return void
     */
    private function transfer_custom_fields($from_res, $to_res, $from_res_id, $to_res_id)
    {
        if (!is_custom_fields_smart_transfer_enabled()) return false;

        $this->db->where('fieldto', $from_res);
        $this->db->where('active', 1);
        $from_res_custom_feilds = $this->db->get(db_prefix() . 'customfields')->result_array();
        foreach ($from_res_custom_feilds as $field) {

            // Replace source resources i.e delivery_ntoe_sku_xxx becomes _sku_xxx
            $tmpSlug = str_ireplace($from_res, '', $field['slug']);
            // Split and get the slug i.e _sku_xxx
            $tmpSlug = explode('_', $tmpSlug, 2);

            if (isset($tmpSlug[1])) {
                $this->db->where('fieldto', $to_res);

                $this->db->group_start();
                $this->db->like('slug', $to_res . '_' . $tmpSlug[1], 'after');
                $this->db->where('type', $field['type']);
                $this->db->where('options', $field['options']);
                $this->db->where('active', 1);
                $this->db->group_end();

                $cfTransfer = $this->db->get(db_prefix() . 'customfields')->result_array();

                $value = get_custom_field_value($from_res_id, $field['id'], $from_res, false);

                // Don't make mistakes
                // Only valid if 1 result returned
                // + if field names similarity is equal or more then CUSTOM_FIELD_TRANSFER_SIMILARITY%
                if (count($cfTransfer) == 1 && ((similarity($field['name'], $cfTransfer[0]['name']) * 100) >= CUSTOM_FIELD_TRANSFER_SIMILARITY)) {
                    if ($value == '') {
                        continue;
                    }

                    $this->db->insert(db_prefix() . 'customfieldsvalues', [
                        'relid'   => $to_res_id,
                        'fieldid' => $cfTransfer[0]['id'],
                        'fieldto' => $to_res,
                        'value'   => $value,
                    ]);
                } else if ((int)get_option('purchase_order_allow_transfer_of_non_similar_custom_fields')) { // If no similar field found
                    // Create the custom field
                    $new_field = $field;
                    $new_field['id'] = null;
                    $new_field['fieldto'] = $to_res;
                    $new_field['slug'] = str_ireplace($from_res, $to_res, $field['slug']);

                    $this->db->insert(db_prefix() . 'customfields', $new_field);
                    $new_field_id = $this->db->insert_id();

                    $this->db->insert(db_prefix() . 'customfieldsvalues', [
                        'relid'   => $to_res_id,
                        'fieldid' => $new_field_id,
                        'fieldto' => $to_res,
                        'value'   => $value,
                    ]);
                }
            }
        }
    }

    /**
     * Copy purchase_order
     * @param mixed $id purchase_order id to copy
     * @return mixed
     */
    public function copy($id)
    {
        $_purchase_order                       = $this->get($id);
        $new_purchase_order_data               = [];
        $new_purchase_order_data['clientid']   = $_purchase_order->clientid;
        $new_purchase_order_data['project_id'] = $_purchase_order->project_id;
        $new_purchase_order_data['number']     = get_option('next_purchase_order_number');
        $new_purchase_order_data['date']       = _d(date('Y-m-d'));

        $new_purchase_order_data['show_quantity_as'] = $_purchase_order->show_quantity_as;
        $new_purchase_order_data['currency']         = $_purchase_order->currency;
        $new_purchase_order_data['subtotal']         = $_purchase_order->subtotal;
        $new_purchase_order_data['total']            = $_purchase_order->total;
        $new_purchase_order_data['adminnote']        = $_purchase_order->adminnote;
        $new_purchase_order_data['adjustment']       = $_purchase_order->adjustment;
        $new_purchase_order_data['discount_percent'] = $_purchase_order->discount_percent;
        $new_purchase_order_data['discount_total']   = $_purchase_order->discount_total;
        $new_purchase_order_data['discount_type']    = $_purchase_order->discount_type;
        $new_purchase_order_data['terms']            = $_purchase_order->terms;
        $new_purchase_order_data['sale_agent']       = $_purchase_order->sale_agent;
        $new_purchase_order_data['reference_no']     = $_purchase_order->reference_no;
        // Since version 1.0.6
        $new_purchase_order_data['billing_street']   = clear_textarea_breaks($_purchase_order->billing_street);
        $new_purchase_order_data['billing_city']     = $_purchase_order->billing_city;
        $new_purchase_order_data['billing_state']    = $_purchase_order->billing_state;
        $new_purchase_order_data['billing_zip']      = $_purchase_order->billing_zip;
        $new_purchase_order_data['billing_country']  = $_purchase_order->billing_country;
        $new_purchase_order_data['shipping_street']  = clear_textarea_breaks($_purchase_order->shipping_street);
        $new_purchase_order_data['shipping_city']    = $_purchase_order->shipping_city;
        $new_purchase_order_data['shipping_state']   = $_purchase_order->shipping_state;
        $new_purchase_order_data['shipping_zip']     = $_purchase_order->shipping_zip;
        $new_purchase_order_data['shipping_country'] = $_purchase_order->shipping_country;
        if ($_purchase_order->include_shipping == 1) {
            $new_purchase_order_data['include_shipping'] = $_purchase_order->include_shipping;
        }
        $new_purchase_order_data['show_shipping_on_purchase_order'] = $_purchase_order->show_shipping_on_purchase_order;
        // Set to unpaid status automatically
        $new_purchase_order_data['status']     = 1;
        $new_purchase_order_data['clientnote'] = $_purchase_order->clientnote;
        $new_purchase_order_data['adminnote']  = '';
        $new_purchase_order_data['newitems']   = [];
        $custom_fields_items             = get_custom_fields('items');
        $key                             = 1;
        foreach ($_purchase_order->items as $item) {
            $new_purchase_order_data['newitems'][$key]['description']      = $item['description'];
            $new_purchase_order_data['newitems'][$key]['long_description'] = clear_textarea_breaks($item['long_description']);
            $new_purchase_order_data['newitems'][$key]['qty']              = $item['qty'];
            $new_purchase_order_data['newitems'][$key]['unit']             = $item['unit'];
            $new_purchase_order_data['newitems'][$key]['taxname']          = [];
            $taxes                                                   = get_purchase_order_item_taxes($item['id']);
            foreach ($taxes as $tax) {
                // tax name is in format TAX1|10.00
                array_push($new_purchase_order_data['newitems'][$key]['taxname'], $tax['taxname']);
            }
            $new_purchase_order_data['newitems'][$key]['rate']  = $item['rate'];
            $new_purchase_order_data['newitems'][$key]['order'] = $item['item_order'];
            foreach ($custom_fields_items as $cf) {
                $new_purchase_order_data['newitems'][$key]['custom_fields']['items'][$cf['id']] = get_custom_field_value($item['id'], $cf['id'], 'items', false);

                if (!defined('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST')) {
                    define('COPY_CUSTOM_FIELDS_LIKE_HANDLE_POST', true);
                }
            }
            $key++;
        }
        $id = $this->add($new_purchase_order_data);
        if ($id) {
            $custom_fields = get_custom_fields('purchase_order');
            foreach ($custom_fields as $field) {
                $value = get_custom_field_value($_purchase_order->id, $field['id'], 'purchase_order', false);
                if ($value == '') {
                    continue;
                }

                $this->db->insert(db_prefix() . 'customfieldsvalues', [
                    'relid'   => $id,
                    'fieldid' => $field['id'],
                    'fieldto' => 'purchase_order',
                    'value'   => $value,
                ]);
            }

            $tags = get_tags_in($_purchase_order->id, 'purchase_order');
            handle_tags_save($tags, $id, 'purchase_order');

            log_activity('Copied purchase_order ' . format_purchase_order_number($_purchase_order->id));

            return $id;
        }

        return false;
    }

    /**
     * Performs purchase_orders totals status
     * @param array $data
     * @return array
     */
    public function get_purchase_orders_total($data)
    {
        $statuses            = $this->get_statuses();
        $has_permission_view = staff_can('view',  'purchase_orders');
        $this->load->model('currencies_model');
        if (isset($data['currency'])) {
            $currencyid = $data['currency'];
        } elseif (isset($data['customer_id']) && $data['customer_id'] != '') {
            $currencyid = $this->clients_model->get_customer_default_currency($data['customer_id']);
            if ($currencyid == 0) {
                $currencyid = $this->currencies_model->get_base_currency()->id;
            }
        } elseif (isset($data['project_id']) && $data['project_id'] != '') {
            $this->load->model('projects_model');
            $currencyid = $this->projects_model->get_currency($data['project_id'])->id;
        } else {
            $currencyid = $this->currencies_model->get_base_currency()->id;
        }

        $currency = get_currency($currencyid);
        $where    = '';
        if (isset($data['customer_id']) && $data['customer_id'] != '') {
            $where = ' AND clientid=' . $data['customer_id'];
        }

        if (isset($data['project_id']) && $data['project_id'] != '') {
            $where .= ' AND project_id=' . $data['project_id'];
        }

        if (!$has_permission_view) {
            $where .= ' AND ' . get_purchase_orders_where_sql_for_staff(get_staff_user_id());
        }

        $sql = 'SELECT';
        foreach ($statuses as $purchase_order_status) {
            $sql .= '(SELECT SUM(total) FROM ' . db_prefix() . 'purchase_orders WHERE status=' . $purchase_order_status;
            $sql .= ' AND currency =' . $this->db->escape_str($currencyid);
            if (isset($data['years']) && count($data['years']) > 0) {
                $sql .= ' AND YEAR(date) IN (' . implode(', ', array_map(function ($year) {
                    return get_instance()->db->escape_str($year);
                }, $data['years'])) . ')';
            } else {
                $sql .= ' AND YEAR(date) = ' . date('Y');
            }
            $sql .= $where;
            $sql .= ') as "' . $purchase_order_status . '",';
        }

        $sql     = substr($sql, 0, -1);
        $result  = $this->db->query($sql)->result_array();
        $_result = [];
        $i       = 1;
        foreach ($result as $key => $val) {
            foreach ($val as $status => $total) {
                $_result[$i]['total']         = $total;
                $_result[$i]['symbol']        = $currency->symbol;
                $_result[$i]['currency_name'] = $currency->name;
                $_result[$i]['status']        = $status;
                $i++;
            }
        }
        $_result['currencyid'] = $currencyid;

        return $_result;
    }

    /**
     * Insert new purchase_order to database
     * @param array $data invoiec data
     * @return mixed - false if not insert, purchase_order ID if succes
     */
    public function add($data)
    {
        $data['datecreated'] = date('Y-m-d H:i:s');

        $data['addedfrom'] = get_staff_user_id();

        $data['prefix'] = get_option('purchase_order_prefix');

        $data['number_format'] = get_option('purchase_order_number_format');

        $save_and_send = isset($data['save_and_send']);

        $purchase_orderRequestID = false;
        if (isset($data['purchase_order_request_id'])) {
            $purchase_orderRequestID = $data['purchase_order_request_id'];
            unset($data['purchase_order_request_id']);
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        $data['hash'] = app_generate_hash();
        $tags         = isset($data['tags']) ? $data['tags'] : '';

        $items = [];
        if (isset($data['newitems'])) {
            $items = $data['newitems'];
            unset($data['newitems']);
        }

        $data = $this->map_shipping_columns($data);

        $data['billing_street'] = trim($data['billing_street']);
        $data['billing_street'] = nl2br($data['billing_street']);

        if (isset($data['shipping_street'])) {
            $data['shipping_street'] = trim($data['shipping_street']);
            $data['shipping_street'] = nl2br($data['shipping_street']);
        }

        if (!isset($data['created_by']) && !is_client_logged_in()) {
            $data['created_by'] = get_staff_user_id();
        }

        $hook = hooks()->apply_filters('before_purchase_order_added', [
            'data'  => $data,
            'items' => $items,
        ]);

        $data  = $hook['data'];
        $items = $hook['items'];

        $this->db->insert(db_prefix() . 'purchase_orders', $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            // Update next purchase_order number in settings
            $this->db->where('name', 'next_purchase_order_number');
            $this->db->set('value', 'value+1', false);
            $this->db->update(db_prefix() . 'options');

            if ($purchase_orderRequestID !== false && $purchase_orderRequestID != '') {
                $this->load->model('purchase_order_request_model');
                $completedStatus = $this->purchase_order_request_model->get_status_by_flag('completed');
                $this->purchase_order_request_model->update_request_status([
                    'requestid' => $purchase_orderRequestID,
                    'status'    => $completedStatus->id,
                ]);
            }

            if (isset($custom_fields)) {
                handle_custom_fields_post($insert_id, $custom_fields);
            }

            handle_tags_save($tags, $insert_id, 'purchase_order');

            foreach ($items as $key => $item) {
                if ($itemid = add_new_sales_item_post($item, $insert_id, 'purchase_order')) {
                    _maybe_insert_post_item_tax($itemid, $item, $insert_id, 'purchase_order');
                }
            }

            update_sales_total_tax_column($insert_id, 'purchase_order', db_prefix() . 'purchase_orders');
            $this->log_purchase_order_activity($insert_id, 'purchase_order_activity_created', false);

            hooks()->do_action('after_purchase_order_added', $insert_id);

            if ($save_and_send === true) {
                $this->send_purchase_order_to_client($insert_id, '', true, '', true);
            }

            return $insert_id;
        }

        return false;
    }

    /**
     * Get item by id
     * @param mixed $id item id
     * @return object
     */
    public function get_purchase_order_item($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'itemable')->row();
    }

    /**
     * Update purchase_order data
     * @param array $data purchase_order data
     * @param mixed $id purchase_orderid
     * @return boolean
     */
    public function update($data, $id)
    {
        $affectedRows = 0;

        $data['number'] = trim($data['number']);

        $original_purchase_order = $this->get($id);

        $original_status = $original_purchase_order->status;

        $original_number = $original_purchase_order->number;

        $original_number_formatted = format_purchase_order_number($id);

        $save_and_send = isset($data['save_and_send']);

        $items = [];
        if (isset($data['items'])) {
            $items = $data['items'];
            unset($data['items']);
        }

        $newitems = [];
        if (isset($data['newitems'])) {
            $newitems = $data['newitems'];
            unset($data['newitems']);
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            if (handle_custom_fields_post($id, $custom_fields)) {
                $affectedRows++;
            }
            unset($data['custom_fields']);
        }

        if (isset($data['tags'])) {
            if (handle_tags_save($data['tags'], $id, 'purchase_order')) {
                $affectedRows++;
            }
        }

        $data['billing_street'] = trim($data['billing_street']);
        $data['billing_street'] = nl2br($data['billing_street']);

        $data['shipping_street'] = trim($data['shipping_street']);
        $data['shipping_street'] = nl2br($data['shipping_street']);

        $data = $this->map_shipping_columns($data);

        $hook = hooks()->apply_filters('before_purchase_order_updated', [
            'data'          => $data,
            'items'         => $items,
            'newitems'      => $newitems,
            'removed_items' => isset($data['removed_items']) ? $data['removed_items'] : [],
        ], $id);

        $data                  = $hook['data'];
        $items                 = $hook['items'];
        $newitems              = $hook['newitems'];
        $data['removed_items'] = $hook['removed_items'];

        // Delete items checked to be removed from database
        foreach ($data['removed_items'] as $remove_item_id) {
            $original_item = $this->get_purchase_order_item($remove_item_id);
            if (handle_removed_sales_item_post($remove_item_id, 'purchase_order')) {
                $affectedRows++;
                $this->log_purchase_order_activity($id, 'invoice_purchase_order_activity_removed_item', false, serialize([
                    $original_item->description,
                ]));
            }
        }

        unset($data['removed_items']);

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'purchase_orders', $data);

        if ($this->db->affected_rows() > 0) {
            // Check for status change
            if ($original_status != $data['status']) {
                $this->log_purchase_order_activity($original_purchase_order->id, 'not_purchase_order_status_updated', false, serialize([
                    '<original_status>' . $original_status . '</original_status>',
                    '<new_status>' . $data['status'] . '</new_status>',
                ]));
                if ($data['status'] == 2) {
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'purchase_orders', ['sent' => 1, 'datesend' => date('Y-m-d H:i:s')]);
                }
            }
            if ($original_number != $data['number']) {
                $this->log_purchase_order_activity($original_purchase_order->id, 'purchase_order_activity_number_changed', false, serialize([
                    $original_number_formatted,
                    format_purchase_order_number($original_purchase_order->id),
                ]));
            }
            $affectedRows++;
        }

        foreach ($items as $key => $item) {
            $original_item = $this->get_purchase_order_item($item['itemid']);

            if (update_sales_item_post($item['itemid'], $item, 'item_order')) {
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'unit')) {
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'rate')) {
                $this->log_purchase_order_activity($id, 'invoice_purchase_order_activity_updated_item_rate', false, serialize([
                    $original_item->rate,
                    $item['rate'],
                ]));
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'qty')) {
                $this->log_purchase_order_activity($id, 'invoice_purchase_order_activity_updated_qty_item', false, serialize([
                    $item['description'],
                    $original_item->qty,
                    $item['qty'],
                ]));
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'description')) {
                $this->log_purchase_order_activity($id, 'invoice_purchase_order_activity_updated_item_short_description', false, serialize([
                    $original_item->description,
                    $item['description'],
                ]));
                $affectedRows++;
            }

            if (update_sales_item_post($item['itemid'], $item, 'long_description')) {
                $this->log_purchase_order_activity($id, 'invoice_purchase_order_activity_updated_item_long_description', false, serialize([
                    $original_item->long_description,
                    $item['long_description'],
                ]));
                $affectedRows++;
            }

            if (isset($item['custom_fields'])) {
                if (handle_custom_fields_post($item['itemid'], $item['custom_fields'])) {
                    $affectedRows++;
                }
            }

            if (!isset($item['taxname']) || (isset($item['taxname']) && count($item['taxname']) == 0)) {
                if (delete_taxes_from_item($item['itemid'], 'purchase_order')) {
                    $affectedRows++;
                }
            } else {
                $item_taxes        = get_purchase_order_item_taxes($item['itemid']);
                $_item_taxes_names = [];
                foreach ($item_taxes as $_item_tax) {
                    array_push($_item_taxes_names, $_item_tax['taxname']);
                }

                $i = 0;
                foreach ($_item_taxes_names as $_item_tax) {
                    if (!in_array($_item_tax, $item['taxname'])) {
                        $this->db->where('id', $item_taxes[$i]['id'])
                            ->delete(db_prefix() . 'item_tax');
                        if ($this->db->affected_rows() > 0) {
                            $affectedRows++;
                        }
                    }
                    $i++;
                }
                if (_maybe_insert_post_item_tax($item['itemid'], $item, $id, 'purchase_order')) {
                    $affectedRows++;
                }
            }
        }

        foreach ($newitems as $key => $item) {
            if ($new_item_added = add_new_sales_item_post($item, $id, 'purchase_order')) {
                _maybe_insert_post_item_tax($new_item_added, $item, $id, 'purchase_order');
                $this->log_purchase_order_activity($id, 'invoice_purchase_order_activity_added_item', false, serialize([
                    $item['description'],
                ]));
                $affectedRows++;
            }
        }

        if ($affectedRows > 0) {
            update_sales_total_tax_column($id, 'purchase_order', db_prefix() . 'purchase_orders');
        }

        if ($save_and_send === true) {
            $this->send_purchase_order_to_client($id, '', true, '', true);
        }

        if ($affectedRows > 0) {
            hooks()->do_action('after_purchase_order_updated', $id);

            return true;
        }

        return false;
    }

    public function mark_action_status($action, $id, $client = false)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'purchase_orders', [
            'status' => $action,
        ]);

        $notifiedUsers = [];

        if ($this->db->affected_rows() > 0) {
            $purchase_order = $this->get($id);

            // Not allowed from client
            if ($client == true) {
                return false;
            }

            $editor = get_staff();

            $this->db->where('staffid', $purchase_order->addedfrom);
            $this->db->or_where('staffid', $purchase_order->sale_agent);
            $staff_purchase_order = $this->db->get(db_prefix() . 'staff')->result_array();


            $invoiceid = false;

            $contact_id = get_primary_contact_user_id($purchase_order->clientid);

            if ($action == 4) {
                if (get_option('purchase_order_auto_convert_to_invoice_on_staff_confirm') == 1) {
                    $invoiceid = $this->convert_to_invoice($id, false);
                    $this->load->model('invoices_model');
                    if ($invoiceid) {
                        $invoice  = $this->invoices_model->get($invoiceid);
                        $this->log_purchase_order_activity($id, 'purchase_order_activity_confirmed_and_converted', false, serialize([
                            '<a href="' . admin_url('invoices/list_invoices/' . $invoiceid) . '">' . format_invoice_number($invoice->id) . '</a>',
                        ]));
                    }
                } else {
                    $this->log_purchase_order_activity($id, 'purchase_order_activity_confirmed', false);
                }

                foreach ($staff_purchase_order as $member) {
                    $notified = add_notification([
                        'fromcompany'     => true,
                        'touserid'        => $member['staffid'],
                        'description'     => 'not_purchase_order_confirmed',
                        'link'            => 'purchase_orders/list_purchase_orders/' . $id,
                        'additional_data' => serialize([
                            format_purchase_order_number($purchase_order->id)
                        ]),
                    ]);

                    if ($notified) {
                        array_push($notifiedUsers, $member['staffid']);
                    }

                    send_mail_template('purchase_order_confirmed_to_staff', PURCHASE_ORDER_MODULE_NAME, $purchase_order, $member, $contact_id, $editor);
                }

                pusher_trigger_notification($notifiedUsers);
                hooks()->do_action('purchase_order_confirmed', $id);
            } elseif ($action == 3) {
                foreach ($staff_purchase_order as $member) {
                    $notified = add_notification([
                        'fromcompany'     => true,
                        'touserid'        => $member['staffid'],
                        'description'     => 'not_purchase_order_cancelled',
                        'link'            => 'purchase_orders/list_purchase_orders/' . $id,
                        'additional_data' => serialize([
                            format_purchase_order_number($purchase_order->id)
                        ]),
                    ]);

                    if ($notified) {
                        array_push($notifiedUsers, $member['staffid']);
                    }
                    // Send staff email notification that purchase order is cancelled
                    send_mail_template('purchase_order_cancelled_to_staff', PURCHASE_ORDER_MODULE_NAME, $purchase_order, $member, $contact_id, $editor);
                }

                pusher_trigger_notification($notifiedUsers);
                $this->log_purchase_order_activity($id, 'purchase_order_activity_cancelled', false);
                hooks()->do_action('purchase_order_cancelled', $id);
            } else {

                foreach ($staff_purchase_order as $member) {
                    $notified = add_notification([
                        'fromcompany'     => true,
                        'touserid'        => $member['staffid'],
                        'description'     => 'not_purchase_order_status_updated',
                        'link'            => 'purchase_orders/list_purchase_orders/' . $id,
                        'additional_data' => serialize([
                            format_purchase_order_number($purchase_order->id),
                            format_purchase_order_status($action)
                        ]),
                    ]);

                    if ($notified) {
                        array_push($notifiedUsers, $member['staffid']);
                    }
                    // Send staff email notification that purchase order is cancelled
                    send_mail_template('purchase_order_status_updated_to_staff', PURCHASE_ORDER_MODULE_NAME, $purchase_order, $member, $contact_id, $editor);
                }

                pusher_trigger_notification($notifiedUsers);

                // Admin marked purchase_order
                $this->log_purchase_order_activity($id, 'purchase_order_activity_marked', false, serialize([
                    '<status>' . $action . '</status>',
                ]));
            }

            if ($action == 2) {
                $this->db->where('id', $id);
                $this->db->update(db_prefix() . 'purchase_orders', ['sent' => 1, 'datesend' => date('Y-m-d H:i:s')]);
            }

            return true;
        }

        return false;
    }

    /**
     * Get purchase_order attachments
     * @param mixed $purchase_orderid
     * @param string $id attachment id
     * @return mixed
     */
    public function get_attachments($purchase_orderid, $id = '')
    {
        // If is passed id get return only 1 attachment
        if (is_numeric($id)) {
            $this->db->where('id', $id);
        } else {
            $this->db->where('rel_id', $purchase_orderid);
        }
        $this->db->where('rel_type', 'purchase_order');
        $result = $this->db->get(db_prefix() . 'files');
        if (is_numeric($id)) {
            return $result->row();
        }

        return $result->result_array();
    }

    /**
     *  Delete purchase_order attachment
     * @param mixed $id attachmentid
     * @return  boolean
     */
    public function delete_attachment($id)
    {
        $attachment = $this->get_attachments('', $id);
        $deleted    = false;
        if ($attachment) {
            if (empty($attachment->external)) {
                unlink(get_upload_path_by_type('purchase_order') . $attachment->rel_id . '/' . $attachment->file_name);
            }
            $this->db->where('id', $attachment->id);
            $this->db->delete(db_prefix() . 'files');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                log_activity('purchase_order Attachment Deleted [purchase_orderID: ' . $attachment->rel_id . ']');
            }

            if (is_dir(get_upload_path_by_type('purchase_order') . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('purchase_order') . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    // okey only index.html so we can delete the folder also
                    delete_dir(get_upload_path_by_type('purchase_order') . $attachment->rel_id);
                }
            }
        }

        return $deleted;
    }

    /**
     * Delete purchase_order items and all connections
     * @param mixed $id purchase_orderid
     * @return boolean
     */
    public function delete($id, $simpleDelete = false)
    {
        if (get_option('delete_only_on_last_purchase_order') == 1 && $simpleDelete == false) {
            if (!is_last_purchase_order($id)) {
                return false;
            }
        }
        $purchase_order = $this->get($id);
        if (!is_null($purchase_order->invoiceid) && $simpleDelete == false) {
            if (!empty($this->invoices_model->get($purchase_order->invoiceid)))
                return [
                    'is_invoiced_purchase_order_delete_error' => true,
                ];
        }
        hooks()->do_action('before_purchase_order_deleted', $id);

        $number = format_purchase_order_number($id);

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'purchase_orders');

        if ($this->db->affected_rows() > 0) {
            if (!is_null($purchase_order->short_link)) {
                app_archive_short_link($purchase_order->short_link);
            }

            if (get_option('purchase_order_number_decrement_on_delete') == 1 && $simpleDelete == false) {
                $current_next_purchase_order_number = get_option('next_purchase_order_number');
                if ($current_next_purchase_order_number > 1) {
                    // Decrement next purchase_order number to
                    $this->db->where('name', 'next_purchase_order_number');
                    $this->db->set('value', 'value-1', false);
                    $this->db->update(db_prefix() . 'options');
                }
            }

            if (total_rows(db_prefix() . 'estimates', [
                'purchase_orderid' => $id,
            ]) > 0) {
                $this->db->where('purchase_orderid', $purchase_order->id);
                $this->db->update(db_prefix() . 'estimates', [
                    'purchase_orderid'    => null,
                ]);
            }

            delete_tracked_emails($id, 'purchase_order');

            $this->db->where('relid IN (SELECT id from ' . db_prefix() . 'itemable WHERE rel_type="purchase_order" AND rel_id="' . $this->db->escape_str($id) . '")');
            $this->db->where('fieldto', 'items');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'purchase_order');
            $this->db->delete(db_prefix() . 'notes');

            $this->db->where('rel_type', 'purchase_order');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'views_tracking');

            $this->db->where('rel_type', 'purchase_order');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'taggables');

            $this->db->where('rel_type', 'purchase_order');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'reminders');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'purchase_order');
            $this->db->delete(db_prefix() . 'itemable');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'purchase_order');
            $this->db->delete(db_prefix() . 'item_tax');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'purchase_order');
            $this->db->delete(db_prefix() . 'sales_activity');

            // Delete the custom field values
            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'purchase_order');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $attachments = $this->get_attachments($id);
            foreach ($attachments as $attachment) {
                $this->delete_attachment($attachment['id']);
            }

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'purchase_order');
            $this->db->delete('scheduled_emails');

            // Get related tasks
            $this->db->where('rel_type', 'purchase_order');
            $this->db->where('rel_id', $id);
            $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();
            foreach ($tasks as $task) {
                $this->tasks_model->delete_task($task['id']);
            }
            if ($simpleDelete == false) {
                log_activity('Purchase_order Deleted [Number: ' . $number . ']');
            }

            hooks()->do_action('after_purchase_order_deleted', $id);

            return true;
        }

        return false;
    }

    /**
     * Set purchase_order to sent when email is successfuly sended to client
     * @param mixed $id purchase_orderid
     */
    public function set_purchase_order_sent($id, $emails_sent = [])
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'purchase_orders', [
            'sent'     => 1,
            'datesend' => date('Y-m-d H:i:s'),
        ]);

        $this->log_purchase_order_activity($id, 'invoice_purchase_order_activity_sent_to_client', false, serialize([
            '<custom_data>' . implode(', ', $emails_sent) . '</custom_data>',
        ]));

        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'purchase_order');
        $this->db->delete('scheduled_emails');
    }

    /**
     * Send purchase_order to client
     * @param mixed $id purchase_orderid
     * @param string $template email template to sent
     * @param boolean $attachpdf attach purchase_order pdf or not
     * @return boolean
     */
    public function send_purchase_order_to_client($id, $template_name = '', $attachpdf = true, $cc = '', $manually = false)
    {
        $purchase_order = $this->get($id);

        if ($template_name == '') {
            $template_name = 'purchase_order_send_to_customer';
        }

        $purchase_order_number = format_purchase_order_number($purchase_order->id);

        $emails_sent = [];
        $send_to     = [];

        // Manually is used when sending the purchase_order via add/edit area button Save & Send
        if (!DEFINED('CRON') && $manually === false) {
            $send_to = $this->input->post('sent_to');
        } elseif (isset($GLOBALS['scheduled_email_contacts'])) {
            $send_to = $GLOBALS['scheduled_email_contacts'];
        } else {
            $contacts = $this->clients_model->get_contacts(
                $purchase_order->clientid,
                ['active' => 1, 'purchase_order_emails' => 1]
            );

            foreach ($contacts as $contact) {
                array_push($send_to, $contact['id']);
            }
        }

        $status_auto_updated = false;
        $status_now          = $purchase_order->status;

        if (is_array($send_to) && count($send_to) > 0) {
            $i = 0;

            // Auto update status to sent in case when user sends the purchase_order is with status draft
            if ($status_now == 1) {
                $this->db->where('id', $purchase_order->id);
                $this->db->update(db_prefix() . 'purchase_orders', [
                    'status' => 4,
                ]);
                $status_auto_updated = true;
            }

            if ($attachpdf) {
                $_pdf_purchase_order = $this->get($purchase_order->id);
                set_mailing_constant();
                $pdf = purchase_order_pdf($_pdf_purchase_order);

                $attach = $pdf->Output($purchase_order_number . '.pdf', 'S');
            }

            foreach ($send_to as $contact_id) {
                if ($contact_id != '') {
                    // Send cc only for the first contact
                    if (!empty($cc) && $i > 0) {
                        $cc = '';
                    }

                    $contact = $this->clients_model->get_contact($contact_id);

                    if (!$contact) {
                        continue;
                    }

                    $template = mail_template($template_name, PURCHASE_ORDER_MODULE_NAME, $purchase_order, $contact, $cc);

                    if ($attachpdf) {
                        $hook = hooks()->apply_filters('send_purchase_order_to_customer_file_name', [
                            'file_name' => str_replace('/', '-', $purchase_order_number . '.pdf'),
                            'purchase_order'  => $_pdf_purchase_order,
                        ]);

                        $template->add_attachment([
                            'attachment' => $attach,
                            'filename'   => $hook['file_name'],
                            'type'       => 'application/pdf',
                        ]);
                    }

                    if ($template->send()) {
                        array_push($emails_sent, $contact->email);
                    }
                }
                $i++;
            }
        } else {
            return false;
        }

        if (count($emails_sent) > 0) {
            $this->set_purchase_order_sent($id, $emails_sent);
            hooks()->do_action('purchase_order_sent', $id);

            return true;
        }

        if ($status_auto_updated) {
            // purchase_order not send to customer but the status was previously updated to sent now we need to revert back to new
            $this->db->where('id', $purchase_order->id);
            $this->db->update(db_prefix() . 'purchase_orders', [
                'status' => $status_now,
            ]);
        }

        return false;
    }

    /**
     * All purchase_order activity
     * @param mixed $id purchase_orderid
     * @return array
     */
    public function get_purchase_order_activity($id)
    {
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'purchase_order');
        $this->db->order_by('date', 'asc');

        return $this->db->get(db_prefix() . 'sales_activity')->result_array();
    }

    /**
     * Log purchase_order activity to database
     * @param mixed $id purchase_orderid
     * @param string $description activity description
     */
    public function log_purchase_order_activity($id, $description = '', $client = false, $additional_data = '')
    {
        $staffid   = get_staff_user_id();
        $full_name = get_staff_full_name(get_staff_user_id());
        if (DEFINED('CRON')) {
            $staffid   = '[CRON]';
            $full_name = '[CRON]';
        } elseif ($client == true) {
            $staffid   = null;
            $full_name = '';
        }

        $this->db->insert(db_prefix() . 'sales_activity', [
            'description'     => $description,
            'date'            => date('Y-m-d H:i:s'),
            'rel_id'          => $id,
            'rel_type'        => 'purchase_order',
            'staffid'         => $staffid,
            'full_name'       => $full_name,
            'additional_data' => $additional_data,
        ]);
    }

    /**
     * Updates pipeline order when drag and drop
     * @param mixe $data $_POST data
     * @return void
     */
    public function update_pipeline($data)
    {
        $this->mark_action_status($data['status'], $data['purchase_orderid']);
        AbstractKanban::updateOrder($data['order'], 'pipeline_order', 'purchase_orders', $data['status']);
    }

    /**
     * Get purchase_order unique year for filtering
     * @return array
     */
    public function get_purchase_orders_years()
    {
        return $this->db->query('SELECT DISTINCT(YEAR(date)) as year FROM ' . db_prefix() . 'purchase_orders ORDER BY year DESC')->result_array();
    }

    private function map_shipping_columns($data)
    {
        if (!isset($data['include_shipping'])) {
            foreach ($this->shipping_fields as $_s_field) {
                if (isset($data[$_s_field])) {
                    $data[$_s_field] = null;
                }
            }
            $data['show_shipping_on_purchase_order'] = 1;
            $data['include_shipping']          = 0;
        } else {
            $data['include_shipping'] = 1;
            // set by default for the next time to be checked
            if (isset($data['show_shipping_on_purchase_order']) && ($data['show_shipping_on_purchase_order'] == 1 || $data['show_shipping_on_purchase_order'] == 'on')) {
                $data['show_shipping_on_purchase_order'] = 1;
            } else {
                $data['show_shipping_on_purchase_order'] = 0;
            }
        }

        return $data;
    }
}
