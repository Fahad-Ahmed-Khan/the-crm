<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Email_send_to_customer extends Lead_manager_mail_template
{
    protected $for = 'customer';
    protected $email_data;
    public $slug = 'lead-manager-send-email-to-customer';
    public $rel_type = 'lead_manager_mailbox';

    public function __construct($data)
    {
        parent::__construct();
        $this->email_data = (array) $data;
        $this->cc       = $data->to_cc;
    }
    public function build()
    {
        if (!empty($_FILES['attachments']['name'])) {
            if (!class_exists('lead_manager_model')) {
                $this->ci->load->model('lead_manager_model');
            }
            $_attachment = $this->ci->lead_manager_model->get_mail_box_email_attachments($this->email_data['id']);
            foreach ($_attachment as $attachment) {
                $this->add_attachment([
                    'attachment' => LEAD_MANAGER_MAILBOX_FOLDER . $attachment['mailbox_id'] . '/' .  $attachment['file_name'],
                    'filename'   => $attachment['file_name'],
                    'type'       => $attachment['filetype'],
                    'read'       => true,
                ]);
            }
        }
        $this->to($this->email_data['to_email'])
            ->set_rel_id($this->email_data['toid'])
            ->set_merge_fields('lead_manager_mailbox_merge_fields', $this->email_data);
    }
}
