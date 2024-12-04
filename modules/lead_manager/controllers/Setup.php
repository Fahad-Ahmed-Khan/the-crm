<?php

use app\services\utilities\Arr;

 defined('BASEPATH') or exit('No direct script access allowed');

class Setup extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('lead_manager_model');
    }

    public function whatsapp_template()
    {
        if ($this->input->post() && $this->input->is_ajax_request()) {
            $response = array('status' => 'danger', 'message' => 'Fail to add template!');
            $id= $this->lead_manager_model->add_whatsapp_template($this->input->post());
            if(is_numeric($id)){
                $response = array('status' => 'success', 'message' => 'Template added!');
            }
            echo json_encode($response); die;
        }
        handle_db_after_installation();
        $data['title'] = _l('lm_whatsapp_template_page_title');
        $data['languages'] = $this->app->get_available_languages();
        $data['staff'] = get_staff();
        $this->load->view('admin/setup/whatsapp_templates', $data);
    }
    
    public function whatsapp_templates_table()
    {
        if (!is_admin()) {
            ajax_access_denied();
        }
        $this->app->get_table_data(module_views_path('lead_manager', 'admin/setup/whatsapp_templates_table'));
    }
    function del_template(){
        if ($this->input->post() && $this->input->is_ajax_request()) {
            $response = array('status' => 'danger', 'message' => 'Fail to delete template!');
            $temp_id = $this->input->post('temp_id');
            if($this->lead_manager_model->delete_whatsapp_template($temp_id)){
                $response = array('status' => 'success', 'message' => 'Template deleted!');
            }
            echo json_encode($response); die;
        }
    }
}