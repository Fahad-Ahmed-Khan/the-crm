<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Styleflow extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('styleflow_model');
	//Nulled	
        //hooks()->do_action('styleflow_init');
    }

    public function index()
    {
        show_404();
    }

    public function manage_invoice_templates()
    {
        if (!has_permission('styleflow', '', 'edit')) {
            access_denied('styleflow');
        }

        $data['title'] = _l('styleflow') . ' - ' . _l('styleflow_invoice_templates');
        $this->load->view('manage', $data);
    }

    public function activate_invoice_template($theme)
    {
        update_option('styleflow_selected_invoice_template', $theme);
        set_alert('success', _l('updated_successfully', _l('styleflow_invoice_templates')));
        redirect($_SERVER['HTTP_REFERER']);
    }
}
