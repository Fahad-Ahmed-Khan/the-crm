<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Purchase_order_confirmed_to_staff extends App_mail_template
{
    protected $for = 'staff';

    protected $purchase_order;

    protected $staff;

    // Staff editing the resources
    protected $editor;

    protected $contact_id;

    public $slug = 'purchase-order-confirmed-to-staff';

    public $rel_type = 'purchase_order';

    public function __construct($purchase_order, $staff, $contact_id, $editor_staff)
    {
        parent::__construct();

        $this->purchase_order    = $purchase_order;
        $this->staff = (object)$staff;
        $this->contact_id  = $contact_id;
        $this->editor = (object)$editor_staff;
    }

    public function build()
    {
        $this->to($this->staff->email)
            ->set_rel_id($this->purchase_order->id)
            ->set_merge_fields('client_merge_fields', $this->purchase_order->clientid, $this->contact_id)
            ->set_merge_fields('staff_merge_fields', $this->staff->staffid)
            ->set_merge_fields('editor_staff_merge_fields', $this->editor->staffid)
            ->set_merge_fields('purchase_order_merge_fields', $this->purchase_order->id);
    }
}
