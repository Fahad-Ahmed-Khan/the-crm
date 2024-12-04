<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <?php
            echo form_open($this->uri->uri_string(), ['id' => 'purchase_order-form', 'class' => '_transaction_form purchase_order-form']);
            if (isset($purchase_order)) {
                echo form_hidden('isedit');
            }
            ?>
            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700 tw-flex tw-items-center tw-space-x-2">
                    <span>
                        <?php echo isset($purchase_order) ? format_purchase_order_number($purchase_order) : _l('create_new_purchase_order'); ?>
                    </span>
                    <?php echo isset($purchase_order) ? format_purchase_order_status($purchase_order->status) : ''; ?>
                </h4>
                <?php $this->load->view('admin/purchase_orders/purchase_order_template'); ?>
            </div>
            <?php echo form_close(); ?>
            <?php $this->load->view('admin/invoice_items/item'); ?>
        </div>
    </div>
</div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        validate_purchase_order_form();
        // Init accountacy currency symbol
        init_currency();
        // Project ajax search
        init_ajax_project_search_by_customer_id();
        // Maybe items ajax search
        init_ajax_search('items', '#item_select.ajax-search', undefined, admin_url + 'items/search');
    });
</script>
</body>

</html>