<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<table class="table dt-table table-purchase_orders" data-order-col="1" data-order-type="desc">
    <thead>
        <tr>
            <th class="th-purchase-order-number"><?php echo _l('clients_purchase_order_dt_number'); ?></th>
            <th class="th-purchase-order-date"><?php echo _l('clients_purchase_order_dt_date'); ?></th>
            <th class="th-purchase-order-amount"><?php echo _l('clients_purchase_order_dt_amount'); ?></th>
            <th class="th-purchase-order-reference-number"><?php echo _l('reference_no'); ?></th>
            <th class="th-purchase-order-status"><?php echo _l('clients_purchase_order_dt_status'); ?></th>
            <?php
            $custom_fields = get_custom_fields('purchase_order', array('show_on_client_portal' => 1));
            foreach ($custom_fields as $field) { ?>
                <th><?php echo e($field['name']); ?></th>
            <?php } ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($purchase_orders as $purchase_order) { ?>
            <tr>
                <td data-order="<?php echo e($purchase_order['number']); ?>"><a href="<?php echo site_url('purchase_orders/client/po/' . $purchase_order['id'] . '/' . $purchase_order['hash']); ?>" class="purchase-ordernumber"><?php echo e(format_purchase_order_number($purchase_order['id'])); ?></a>
                    <?php
                    if ($purchase_order['invoiceid']) {
                        echo '<br /><span class="text-success">' . _l('purchase_order_invoiced') . '</span>';
                    }
                    ?>
                </td>
                <td data-order="<?php echo e($purchase_order['date']); ?>">
                    <?php echo e(_d($purchase_order['date'])); ?></td>
                <td data-order="<?php echo e($purchase_order['total']); ?>">
                    <?php echo e(app_format_money($purchase_order['total'], $purchase_order['currency_name'])); ?>
                </td>
                <td><?php echo e($purchase_order['reference_no']); ?></td>
                <td><?php echo format_purchase_order_status($purchase_order['status'], 'inline-block', true); ?>
                </td>
                <?php foreach ($custom_fields as $field) { ?>
                    <td><?php echo get_custom_field_value($purchase_order['id'], $field['id'], 'purchase_order'); ?>
                    </td>
                <?php } ?>
            </tr>
        <?php } ?>
    </tbody>
</table>