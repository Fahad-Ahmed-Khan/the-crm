<?php

use app\modules\purchase_orders\services\PurchaseOrderPipeline;

defined('BASEPATH') or exit('No direct script access allowed');

class Purchase_orders extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase_orders_model');
    }

    /* Get all purchase_orders in case user go on index page */
    public function index($id = '')
    {
        $this->list_purchase_orders($id);
    }

    /* List all purchase_orders datatables */
    public function list_purchase_orders($id = '')
    {
        if (staff_cant('view', 'purchase_orders') && staff_cant('view_own', 'purchase_orders') && get_option('allow_staff_view_purchase_orders_assigned') == '0') {
            access_denied('purchase_orders');
        }

        $isPipeline = $this->session->userdata('purchase_order_pipeline') == 'true';

        $data['purchase_order_statuses'] = $this->purchase_orders_model->get_statuses();

        if ($isPipeline && !$this->input->get('status') && !$this->input->get('filter')) {
            $data['title']           = _l('purchase_orders_pipeline');
            $data['bodyclass']       = 'purchase_orders-pipeline purchase_orders-total-manual';
            $data['switch_pipeline'] = false;

            if (is_numeric($id)) {
                $data['purchase_orderid'] = $id;
            } else {
                $data['purchase_orderid'] = $this->session->flashdata('purchase_orderid');
            }

            $this->load->view('admin/purchase_orders/pipeline/manage', $data);
        } else {

            // Pipeline was initiated but user click from home page and need to show table only to filter
            if ($this->input->get('status') || $this->input->get('filter') && $isPipeline) {
                $this->pipeline(0, true);
            }

            $data['purchase_orderid']            = $id;
            $data['switch_pipeline']       = true;
            $data['title']                 = _l('purchase_orders');
            $data['bodyclass']             = 'purchase_orders-total-manual';
            $data['purchase_orders_years']       = $this->purchase_orders_model->get_purchase_orders_years();
            $data['purchase_orders_sale_agents'] = $this->purchase_orders_model->get_sale_agents();

            $this->load->view('admin/purchase_orders/manage', $data);
        }
    }

    public function table($clientid = '')
    {
        if (!has_permission('purchase_orders', '', 'view') && !has_permission('purchase_orders', '', 'view_own') && get_option('allow_staff_view_purchase_orders_assigned') == '0') {
            ajax_access_denied();
        }

        $this->app->get_table_data(module_views_path(PURCHASE_ORDER_MODULE_NAME, 'admin/tables/purchase_orders'), [
            'clientid' => $clientid,
        ]);
    }

    /* Add new purchase_order or update existing */
    public function purchase_order($id = '')
    {
        if ($this->input->post()) {
            $purchase_order_data = $this->input->post();

            $save_and_send_later = false;
            if (isset($purchase_order_data['save_and_send_later'])) {
                unset($purchase_order_data['save_and_send_later']);
                $save_and_send_later = true;
            }

            if ($id == '') {
                if (staff_cant('create', 'purchase_orders')) {
                    access_denied('purchase_orders');
                }
                $id = $this->purchase_orders_model->add($purchase_order_data);

                if ($id) {
                    set_alert('success', _l('added_successfully', _l('purchase_order')));

                    $redUrl = admin_url('purchase_orders/list_purchase_orders/' . $id);

                    if ($save_and_send_later) {
                        $this->session->set_userdata('send_later', true);
                        // die(redirect($redUrl));
                    }

                    redirect(
                        !$this->set_purchase_order_pipeline_autoload($id) ? $redUrl : admin_url('purchase_orders/list_purchase_orders/')
                    );
                }
            } else {
                if (staff_cant('edit', 'purchase_orders')) {
                    access_denied('purchase_orders');
                }
                $success = $this->purchase_orders_model->update($purchase_order_data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('purchase_order')));
                }
                if ($this->set_purchase_order_pipeline_autoload($id)) {
                    redirect(admin_url('purchase_orders/list_purchase_orders/'));
                } else {
                    redirect(admin_url('purchase_orders/list_purchase_orders/' . $id));
                }
            }
        }
        if ($id == '') {
            $title = _l('create_new_purchase_order');
        } else {
            $purchase_order = $this->purchase_orders_model->get($id);

            if (!$purchase_order || !user_can_view_purchase_order($id)) {
                blank_page(_l('purchase_order_not_found'));
            }

            $data['purchase_order'] = $purchase_order;
            $data['edit']     = true;
            $title            = _l('edit', _l('purchase_order_lowercase'));
        }

        if ($this->input->get('customer_id')) {
            $data['customer_id'] = $this->input->get('customer_id');
        }

        if ($this->input->get('purchase_order_request_id')) {
            $data['purchase_order_request_id'] = $this->input->get('purchase_order_request_id');
        }

        $this->load->model('taxes_model');
        $data['taxes'] = $this->taxes_model->get();
        $this->load->model('currencies_model');
        $data['currencies'] = $this->currencies_model->get();

        $data['base_currency'] = $this->currencies_model->get_base_currency();

        $this->load->model('invoice_items_model');

        $data['ajaxItems'] = false;
        if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
            $data['items'] = $this->invoice_items_model->get_grouped();
        } else {
            $data['items']     = [];
            $data['ajaxItems'] = true;
        }
        $data['items_groups'] = $this->invoice_items_model->get_groups();

        $data['staff']             = $this->staff_model->get('', ['active' => 1]);
        $data['purchase_order_statuses'] = $this->purchase_orders_model->get_statuses();
        $data['title']             = $title;
        $this->load->view('admin/purchase_orders/purchase_order', $data);
    }

    public function update_number_settings($id)
    {
        $response = [
            'success' => false,
            'message' => '',
        ];
        if (staff_can('edit',  'purchase_orders')) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'purchase_orders', [
                'prefix' => $this->input->post('prefix'),
            ]);
            if ($this->db->affected_rows() > 0) {
                $response['success'] = true;
                $response['message'] = _l('updated_successfully', _l('purchase_order'));
            }
        }

        echo json_encode($response);
        die;
    }

    public function validate_purchase_order_number()
    {
        $isedit          = $this->input->post('isedit');
        $number          = $this->input->post('number');
        $date            = $this->input->post('date');
        $original_number = $this->input->post('original_number');
        $number          = trim($number);
        $number          = ltrim($number, '0');

        if ($isedit == 'true') {
            if ($number == $original_number) {
                echo json_encode(true);
                die;
            }
        }

        if (total_rows(db_prefix() . 'purchase_orders', [
            'YEAR(date)' => date('Y', strtotime(to_sql_date($date))),
            'number' => $number,
        ]) > 0) {
            echo 'false';
        } else {
            echo 'true';
        }
    }

    public function delete_attachment($id)
    {
        $file = $this->misc_model->get_file($id);
        if ($file->staffid == get_staff_user_id() || is_admin()) {
            echo $this->purchase_orders_model->delete_attachment($id);
        } else {
            header('HTTP/1.0 400 Bad error');
            echo _l('access_denied');
            die;
        }
    }

    /* Get all purchase_order data used when user click on purchase_order number in a datatable left side*/
    public function get_purchase_order_data_ajax($id, $to_return = false)
    {
        if (staff_cant('view', 'purchase_orders') && staff_cant('view_own', 'purchase_orders') && get_option('allow_staff_view_purchase_orders_assigned') == '0') {
            echo _l('access_denied');
            die;
        }

        if (!$id) {
            die('No purchase_order found');
        }

        $purchase_order = $this->purchase_orders_model->get($id);

        if (!$purchase_order || !user_can_view_purchase_order($id)) {
            echo _l('purchase_order_not_found');
            die;
        }

        $purchase_order->date       = _d($purchase_order->date);
        if ($purchase_order->invoiceid !== null) {
            $this->load->model('invoices_model');
            $purchase_order->invoice = $this->invoices_model->get($purchase_order->invoiceid);
        }

        $template_name = 'purchase_order_send_to_customer';

        $data = my_prepare_mail_preview_data($template_name, $purchase_order->clientid, [PURCHASE_ORDER_MODULE_NAME]);

        $data['activity']          = $this->purchase_orders_model->get_purchase_order_activity($id);
        $data['purchase_order']          = $purchase_order;
        $data['members']           = $this->staff_model->get('', ['active' => 1]);
        $data['purchase_order_statuses'] = $this->purchase_orders_model->get_statuses();
        $data['totalNotes']        = total_rows(db_prefix() . 'notes', ['rel_id' => $id, 'rel_type' => 'purchase_order']);

        $data['send_later'] = false;
        if ($this->session->has_userdata('send_later')) {
            $data['send_later'] = true;
            $this->session->unset_userdata('send_later');
        }

        if ($to_return == false) {
            $this->load->view('admin/purchase_orders/purchase_order_preview_template', $data);
        } else {
            return $this->load->view('admin/purchase_orders/purchase_order_preview_template', $data, true);
        }
    }

    public function get_purchase_orders_total()
    {
        if ($this->input->post()) {
            $data['totals'] = $this->purchase_orders_model->get_purchase_orders_total($this->input->post());

            $this->load->model('currencies_model');

            if (!$this->input->post('customer_id')) {
                $multiple_currencies = call_user_func('is_using_multiple_currencies', db_prefix() . 'purchase_orders');
            } else {
                $multiple_currencies = call_user_func('is_client_using_multiple_currencies', $this->input->post('customer_id'), db_prefix() . 'purchase_orders');
            }

            if ($multiple_currencies) {
                $data['currencies'] = $this->currencies_model->get();
            }

            $data['purchase_orders_years'] = $this->purchase_orders_model->get_purchase_orders_years();

            if (
                count($data['purchase_orders_years']) >= 1
                && !\app\services\utilities\Arr::inMultidimensional($data['purchase_orders_years'], 'year', date('Y'))
            ) {
                array_unshift($data['purchase_orders_years'], ['year' => date('Y')]);
            }

            $data['_currency'] = $data['totals']['currencyid'];
            unset($data['totals']['currencyid']);
            $this->load->view('admin/purchase_orders/purchase_orders_total_template', $data);
        }
    }

    public function add_note($rel_id)
    {
        if ($this->input->post() && user_can_view_purchase_order($rel_id)) {
            $this->misc_model->add_note($this->input->post(), 'purchase_order', $rel_id);
            echo $rel_id;
        }
    }

    public function get_notes($id)
    {
        if (user_can_view_purchase_order($id)) {
            $data['notes'] = $this->misc_model->get_notes($id, 'purchase_order');
            $this->load->view('admin/includes/sales_notes_template', $data);
        }
    }

    public function mark_action_status($status, $id)
    {
        if (staff_cant('edit', 'purchase_orders')) {
            access_denied('purchase_orders');
        }
        $success = $this->purchase_orders_model->mark_action_status($status, $id);

        if ($success) {
            set_alert('success', _l('purchase_order_status_changed_success'));
        } else {
            set_alert('danger', _l('purchase_order_status_changed_fail'));
        }
        if ($this->set_purchase_order_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('purchase_orders/list_purchase_orders/' . $id));
        }
    }

    /* Send purchase_order to email */
    public function send_to_email($id)
    {
        $canView = user_can_view_purchase_order($id);
        if (!$canView) {
            access_denied('purchase_orders');
        } else {
            if (staff_cant('view', 'purchase_orders') && staff_cant('view_own', 'purchase_orders') && $canView == false) {
                access_denied('purchase_orders');
            }
        }

        try {
            $success = $this->purchase_orders_model->send_purchase_order_to_client($id, '', $this->input->post('attach_pdf'), $this->input->post('cc'));
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        // In case client use another language
        load_admin_language();
        if ($success) {
            set_alert('success', _l('purchase_order_sent_to_client_success'));
        } else {
            set_alert('danger', _l('purchase_order_sent_to_client_fail'));
        }
        if ($this->set_purchase_order_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('purchase_orders/list_purchase_orders/' . $id));
        }
    }

    /* Convert purchase_order to invoice */
    public function convert_to_invoice($id)
    {
        if (staff_cant('create', 'invoices')) {
            access_denied('invoices');
        }
        if (!$id) {
            die('No purchase_order found');
        }
        $draft_invoice = false;
        if ($this->input->get('save_as_draft')) {
            $draft_invoice = true;
        }
        $invoiceid = $this->purchase_orders_model->convert_to_invoice($id, false, $draft_invoice);
        if ($invoiceid) {
            set_alert('success', _l('purchase_order_convert_to_invoice_successfully'));
            redirect(admin_url('invoices/list_invoices/' . $invoiceid));
        } else {
            if ($this->session->has_userdata('purchase_order_pipeline') && $this->session->userdata('purchase_order_pipeline') == 'true') {
                $this->session->set_flashdata('purchase_orderid', $id);
            }
            if ($this->set_purchase_order_pipeline_autoload($id)) {
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('purchase_orders/list_purchase_orders/' . $id));
            }
        }
    }

    /** convert estimate to purchase order */
    public function convert_from_estimate($estimateid)
    {
        if (staff_cant('create', 'purchase_orders')) {
            access_denied('purchase_orders');
        }

        if (!$estimateid) {
            die('No estimate found');
        }

        $status = 4; //confirmed
        if ($this->input->get('save_as_new')) {
            $status = 1; // new
        }
        $new_id = $this->purchase_orders_model->convert_from_estimate($estimateid, $status);
        if ($new_id) {
            set_alert('success', _l('estimate_convert_to_purchase_order_successfully'));
            redirect(admin_url('purchase_orders/purchase_order/' . $new_id));
        }

        set_alert('danger', _l('estimate_convert_to_purchase_order_fail'));
        if ($this->set_purchase_order_pipeline_autoload($estimateid)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('estimates/estimate/' . $estimateid));
        }
    }

    public function copy($id)
    {
        if (staff_cant('create', 'purchase_orders')) {
            access_denied('purchase_orders');
        }
        if (!$id) {
            die('No purchase_order found');
        }
        $new_id = $this->purchase_orders_model->copy($id);
        if ($new_id) {
            set_alert('success', _l('purchase_order_copied_successfully'));
            if ($this->set_purchase_order_pipeline_autoload($new_id)) {
                redirect($_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('purchase_orders/purchase_order/' . $new_id));
            }
        }
        set_alert('danger', _l('purchase_order_copied_fail'));
        if ($this->set_purchase_order_pipeline_autoload($id)) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('purchase_orders/purchase_order/' . $id));
        }
    }

    /* Delete purchase_order */
    public function delete($id)
    {
        if (staff_cant('delete', 'purchase_orders')) {
            access_denied('purchase_orders');
        }
        if (!$id) {
            redirect(admin_url('purchase_orders/list_purchase_orders'));
        }
        $success = $this->purchase_orders_model->delete($id);
        if (is_array($success)) {
            set_alert('warning', _l('is_invoiced_purchase_order_delete_error'));
        } elseif ($success == true) {
            set_alert('success', _l('deleted', _l('purchase_order')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('purchase_order_lowercase')));
        }
        redirect(admin_url('purchase_orders/list_purchase_orders'));
    }

    public function clear_acceptance_info($id)
    {
        if (is_admin()) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'purchase_orders', get_acceptance_info_array(true));
        }

        redirect(admin_url('purchase_orders/list_purchase_orders/' . $id));
    }

    /* Generates purchase_order PDF and senting to email  */
    public function pdf($id)
    {
        $canView = user_can_view_purchase_order($id);
        if (!$canView) {
            access_denied('Purchase_order');
        } else {
            if (staff_cant('view', 'purchase_orders') && staff_cant('view_own', 'purchase_orders') && $canView == false) {
                access_denied('Purchase_order');
            }
        }
        if (!$id) {
            redirect(admin_url('purchase_orders/list_purchase_orders'));
        }
        $purchase_order        = $this->purchase_orders_model->get($id);
        $purchase_order_number = format_purchase_order_number($purchase_order->id);

        try {
            $pdf = purchase_order_pdf($purchase_order);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $fileNameHookData = hooks()->apply_filters('purchase_order_file_name_admin_area', [
            'file_name' => mb_strtoupper(slug_it($purchase_order_number)) . '.pdf',
            'purchase_order'  => $purchase_order,
        ]);

        $pdf->Output($fileNameHookData['file_name'], $type);
    }

    // Pipeline
    public function get_pipeline()
    {
        if (staff_can('view',  'purchase_orders') || staff_can('view_own',  'purchase_orders') || get_option('allow_staff_view_purchase_orders_assigned') == '1') {
            $data['purchase_order_statuses'] = $this->purchase_orders_model->get_statuses();
            $this->load->view('admin/purchase_orders/pipeline/pipeline', $data);
        }
    }

    public function pipeline_open($id)
    {
        $canView = user_can_view_purchase_order($id);
        if (!$canView) {
            access_denied('Purchase_order');
        } else {
            if (staff_cant('view', 'purchase_orders') && staff_cant('view_own', 'purchase_orders') && $canView == false) {
                access_denied('Purchase_order');
            }
        }

        $data['id']       = $id;
        $data['purchase_order'] = $this->get_purchase_order_data_ajax($id, true);
        $this->load->view('admin/purchase_orders/pipeline/purchase_order', $data);
    }

    public function update_pipeline()
    {
        if (staff_can('edit',  'purchase_orders')) {
            $this->purchase_orders_model->update_pipeline($this->input->post());
        }
    }

    public function pipeline($set = 0, $manual = false)
    {
        if ($set == 1) {
            $set = 'true';
        } else {
            $set = 'false';
        }
        $this->session->set_userdata([
            'purchase_order_pipeline' => $set,
        ]);
        if ($manual == false) {
            redirect(admin_url('purchase_orders/list_purchase_orders'));
        }
    }

    public function pipeline_load_more()
    {
        $status = $this->input->get('status');
        $page   = $this->input->get('page');

        $purchase_orders = (new PurchaseOrderPipeline($status))
            ->search($this->input->get('search'))
            ->sortBy(
                $this->input->get('sort_by'),
                $this->input->get('sort')
            )
            ->page($page)->get();

        foreach ($purchase_orders as $purchase_order) {
            $this->load->view('admin/purchase_orders/pipeline/_kanban_card', [
                'purchase_order' => $purchase_order,
                'status'   => $status,
            ]);
        }
    }

    public function set_purchase_order_pipeline_autoload($id)
    {
        if ($id == '') {
            return false;
        }

        if (
            $this->session->has_userdata('purchase_order_pipeline')
            && $this->session->userdata('purchase_order_pipeline') == 'true'
        ) {
            $this->session->set_flashdata('purchase_orderid', $id);

            return true;
        }

        return false;
    }

    public function zip_purchase_orders($id)
    {
        $has_permission_view = staff_can('view',  'purchase_orders');
        if (
            !$has_permission_view && staff_cant('view_own', 'purchase_orders')
            && get_option('allow_staff_view_purchase_orders_assigned') == '0'
        ) {
            access_denied('Zip Customer delivery notes');
        }

        if ($this->input->post()) {
            $this->load->library('purchase_orders_bulk_pdf_export', [
                'export_type'       => 'purchase_orders',
                'status'            => $this->input->post('purchase_order_zip_status'),
                'date_from'         => $this->input->post('zip-from'),
                'date_to'           => $this->input->post('zip-to'),
                'redirect_on_error' => admin_url('clients/client/' . $id . '?group=purchase_orders'),
            ]);

            $this->purchase_orders_bulk_pdf_export->set_client_id($id);
            $this->purchase_orders_bulk_pdf_export->in_folder($this->input->post('file_name'));
            $this->purchase_orders_bulk_pdf_export->export();
        }
    }
}
