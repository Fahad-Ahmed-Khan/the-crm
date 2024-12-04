<?php

defined('BASEPATH') or exit('No direct script access allowed');


class Zoom_link_send_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $contact_id;
    protected $meeting_data;
    protected $contatct;
    protected $user_email;

    public $slug = 'lead-manager-send-to-customer';

    public $rel_type = 'lead_manager_meeting';

    public function __construct($meeting_data)
    {
        parent::__construct();
        if(!class_exists('clients_model')){
            $this->ci->load->model('clients_model');
        }
        $this->contatct = $this->ci->clients_model->get_contact($meeting_data->leadid);
        $this->contact_id     = $meeting_data->leadid;
        $this->user_email = $meeting_data->email;
        $this->meeting_data = $meeting_data;

    }

    public function build()
    {
        $this->to($this->user_email)
        ->set_rel_id($this->meeting_data->id)
        ->set_merge_fields('lead_manager_meeting_merge_fields', $this->meeting_data)
        ->set_merge_fields('client_merge_fields', $this->contatct->userid, $this->contact_id)
        ->set_merge_fields('staff_merge_fields', $this->meeting_data->staff_id);
    }
}
