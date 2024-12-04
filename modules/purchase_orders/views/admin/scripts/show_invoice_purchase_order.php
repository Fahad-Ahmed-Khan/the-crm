<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
if (!empty($invoice->id)) {
    $purchase_order = $this->purchase_orders_model->db->where('invoiceid', $invoice->id)->get(db_prefix() . 'purchase_orders')->row();
?>
    <div class="invoice_to_purchase_order pull-right">
        <?php if (!empty($purchase_order->id)) { ?>
            <a href="<?php echo admin_url('purchase_orders/list_purchase_orders/' . $purchase_order->id); ?>" class="btn btn-primary mleft10"><?php echo format_purchase_order_number($purchase_order->id); ?></a>
        <?php } ?>
    </div>
<?php } ?>
<script>
    document.querySelector('.panel-body ._buttons .pull-right').appendChild(document.querySelector(
        '.invoice_to_purchase_order'));
</script>