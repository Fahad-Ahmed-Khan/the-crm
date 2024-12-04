<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once(APPPATH . 'controllers/Clients.php');

class Client extends Clients
{
    public function po($id, $hash)
    {
        check_purchase_order_restrictions($id, $hash);
        $purchase_order = $this->purchase_orders_model->get($id);

        if (!is_client_logged_in()) {
            load_client_language($purchase_order->clientid);
        }

        $identity_confirmation_enabled = get_option('purchase_order_accept_identity_confirmation');

        // Handle Estimate PDF generator
        if ($this->input->post('purchase_orderpdf')) {
            try {
                $pdf = purchase_order_pdf($purchase_order);
            } catch (Exception $e) {
                echo $e->getMessage();
                die;
            }

            $purchase_order_number = format_purchase_order_number($purchase_order->id);
            $companyname     = get_option('invoice_company_name');
            if ($companyname != '') {
                $purchase_order_number .= '-' . mb_strtoupper(slug_it($companyname), 'UTF-8');
            }

            $filename = hooks()->apply_filters('customers_area_download_purchase_order_filename', mb_strtoupper(slug_it($purchase_order_number), 'UTF-8') . '.pdf', $purchase_order);

            $pdf->Output($filename, 'D');
            die();
        }
        $this->load->library('app_number_to_word', [
            'clientid' => $purchase_order->clientid,
        ], 'numberword');

        $this->app_scripts->theme('sticky-js', 'assets/plugins/sticky/sticky.js');

        $data['title'] = format_purchase_order_number($purchase_order->id);
        $this->disableNavigation();
        $this->disableSubMenu();
        $data['hash']                          = $hash;
        $data['can_be_accepted']               = false;
        $data['purchase_order']                      = hooks()->apply_filters('purchase_order_html_pdf_data', $purchase_order);
        $data['bodyclass']                     = 'viewpurchase_order';
        $data['identity_confirmation_enabled'] = $identity_confirmation_enabled;
        if ($identity_confirmation_enabled == '1') {
            $data['bodyclass'] .= ' identity-confirmation';
        }
        $this->data($data);
        $this->view('client/purchase_orderhtml', $data, true);
        add_views_tracking('purchase_order', $id);
        hooks()->do_action('purchase_order_html_viewed', $id);
        no_index_customers_area();
        $this->layout();
    }

    public function index($status = '')
    {
        if (!has_contact_permission('purchase_orders')) {
            set_alert('warning', _l('access_denied'));
            redirect(site_url());
        }
        $where = [
            'clientid' => get_client_user_id(),
        ];
        if (is_numeric($status)) {
            $where['status'] = $status;
        }
        if (isset($where['status'])) {
            if ($where['status'] == 1 && get_option('exclude_purchase_order_from_client_area_with_new_status') == 1) {
                unset($where['status']);
                $where['status !='] = 1;
            }
        } else {
            if (get_option('exclude_purchase_order_from_client_area_with_new_status') == 1) {
                $where['status !='] = 1;
            }
        }
        $data['purchase_orders'] = $this->purchase_orders_model->get('', $where);
        $data['title']     = _l('clients_my_purchase_orders');
        $this->data($data);
        $this->view('client/purchase_orders');
        $this->layout();
    }

    /**
     * @inheritDoc
     */
    public function view($view)
    {
        $project_group = $this->input->get('group');
        if ($project_group === PURCHASE_ORDER_MODULE_NAME && $view === 'project') {

            $view = 'client/project_purchase_orders';

            // Inject project purchase orders
            $data['purchase_orders'] = [];
            if (has_contact_permission(PURCHASE_ORDER_MODULE_NAME)) {
                $where_purchase_orders = [
                    'clientid'   => get_client_user_id(),
                    'project_id' => $this->data['project']->id,
                ];

                if (get_option('exclude_purchase_order_from_client_area_with_new_status') == 1) {
                    $where_purchase_orders['status !='] = 1;
                }

                $data['purchase_orders'] = $this->purchase_orders_model->get('', $where_purchase_orders);
                $this->data($data);
            }
        }

        $this->view = $view;

        return $this;
    }
}
