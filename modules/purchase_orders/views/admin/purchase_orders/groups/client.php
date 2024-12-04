<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (isset($client)) { ?>
    <h4 class="customer-profile-group-heading"><?php echo _l(PURCHASE_ORDER_MODULE_NAME); ?></h4>

    <?php if (staff_can('create',  PURCHASE_ORDER_MODULE_NAME)) { ?>
        <a href="<?php echo admin_url('purchase_orders/purchase_order?customer_id=' . $client->userid); ?>" class="btn btn-primary mbot15<?php echo $client->active == 0 ? ' disabled' : ''; ?>">
            <i class="fa-regular fa-plus tw-mr-1"></i>
            <?php echo _l('create_new_purchase_order'); ?>
        </a>
    <?php } ?>

    <?php if (staff_can('view',  PURCHASE_ORDER_MODULE_NAME) || staff_can('view_own',  PURCHASE_ORDER_MODULE_NAME) || get_option('allow_staff_view_purchase_orders_assigned') == '1') { ?>
        <a href="#" class="btn btn-primary mbot15" data-toggle="modal" data-target="#client_zip_purchase_orders">
            <i class="fa-regular fa-file-zipper tw-mr-1"></i>
            <?php echo _l('zip_purchase_orders'); ?>
        </a>
    <?php } ?>
    <div id="purchase_orders_total" class="tw-mb-5"></div>
    <?php
    $this->load->view('purchase_orders/admin/purchase_orders/table_html', ['class' => 'delivery-notes-single-client']);
    $this->load->view('purchase_orders/admin/purchase_orders/groups/client_zip', ['purchase_order_statuses' => $this->purchase_orders_model->get_statuses()]);
    ?>
<?php } ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initDataTable('.table-delivery-notes-single-client',
            admin_url + "purchase_orders/table/" + customer_id,
            'undefined',
            'undefined',
            'undefined', [
                [3, 'desc'],
                [0, 'desc']
            ]);
    });
</script>