<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once(APPPATH . 'libraries/App_bulk_pdf_export.php');

class Purchase_orders_bulk_pdf_export extends App_bulk_pdf_export
{
    /**
     * Create purchase_orders export
     * @return object
     */
    protected function purchase_orders()
    {
        $noPermissionQuery = get_purchase_orders_where_sql_for_staff(get_staff_user_id());

        $this->ci->db->select('id');
        $this->ci->db->from(db_prefix() . 'purchase_orders');

        if ($this->status != 'all') {
            $this->ci->db->where('status', $this->status);
        }

        if (!$this->can_view) {
            $this->ci->db->where($noPermissionQuery);
        }

        $this->ci->db->order_by($this->get_date_column(), 'desc');

        $data = $this->finalize();
        $this->ci->load->model('purchase_orders_model');
        foreach ($data as $purchase_order) {
            $purchase_order = $this->ci->purchase_orders_model->get($purchase_order['id']);
            $pdf      = purchase_order_pdf($purchase_order, $this->pdf_tag);
            $this->save_to_dir($purchase_order, $pdf, strtoupper(slug_it(format_purchase_order_number($purchase_order->id))) . '.pdf');
        }

        return $this;
    }
}
