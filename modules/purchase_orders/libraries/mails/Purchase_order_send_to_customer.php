<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Purchase_order_send_to_customer extends App_mail_template
{
    protected $for = 'customer';

    protected $purchase_order;

    protected $contact;

    public $slug = 'purchase-order-send-to-client';

    public $rel_type = 'purchase_order';

    public function __construct($purchase_order, $contact, $cc = '')
    {
        parent::__construct();

        $this->purchase_order = $purchase_order;
        $this->contact = $contact;
        $this->cc      = $cc;
    }

    public function build()
    {
        if ($this->ci->input->post('email_attachments')) {
            $_other_attachments = $this->ci->input->post('email_attachments');
            foreach ($_other_attachments as $attachment) {
                $_attachment = $this->ci->purchase_orders_model->get_attachments($this->purchase_order->id, $attachment);
                $this->add_attachment([
                    'attachment' => get_upload_path_by_type('purchase_order') . $this->purchase_order->id . '/' . $_attachment->file_name,
                    'filename'   => $_attachment->file_name,
                    'type'       => $_attachment->filetype,
                    'read'       => true,
                ]);
            }
        }

        $this->to($this->contact->email)
            ->set_rel_id($this->purchase_order->id)
            ->set_merge_fields('client_merge_fields', $this->purchase_order->clientid, $this->contact->id)
            ->set_merge_fields('purchase_order_merge_fields', $this->purchase_order->id);
    }
}
