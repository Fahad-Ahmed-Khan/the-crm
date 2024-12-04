<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700 section-heading section-heading-purchase_orders">
    <?php echo _l('clients_my_purchase_orders'); ?>
</h4>
<div class="panel_s">
    <div class="panel-body">

        <?php
        $where_total = ['clientid' => get_client_user_id()];
        $exclude_new = get_option('exclude_purchase_order_from_client_area_with_new_status') == 1;
        if ($exclude_new) {
            $where_total['status !='] = 1;
        }
        $total_purchase_orders = total_rows(db_prefix() . 'purchase_orders', $where_total);
        $purchase_order_statuses = $this->purchase_orders_model->get_statuses();
        $col_class = $exclude_new ? 'col-md-6' : 'col-md-4';
        ?>
        <div class="row text-left purchase_orders-stats">

            <?php foreach ($purchase_order_statuses as $status) {
                if ($status === 1 && $exclude_new) {
                    continue;
                }
                $total = total_rows(db_prefix() . 'purchase_orders', ['status' => $status, 'clientid' => get_client_user_id()]);
                $percent     = ($total_purchase_orders > 0 ? number_format(($total * 100) / $total_purchase_orders, 2) : 0);
            ?>
                <div class="<?php echo e($col_class); ?>">
                    <div class="row">
                        <div class="col-md-8 stats-status">
                            <a href="<?php echo site_url('clients/purchase_orders/' . $status); ?>" class="tw-text-neutral-600 hover:tw-text-neutral-800 active:tw-text-neutral-800 tw-font-medium">
                                <?php echo format_purchase_order_status($status, '', false); ?>
                            </a>
                        </div>
                        <div class="col-md-4 text-right bold stats-numbers">
                            <?php echo e($total); ?> / <?php echo e($total_purchase_orders); ?>
                        </div>
                        <div class="col-md-12 tw-mt-1.5">
                            <div class="progress">
                                <div class="progress-bar progress-bar-<?php echo purchase_order_status_color_class($status); ?>" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width: 0%" data-percent="<?php echo e($percent); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>

        </div>


        <hr />


        <?php require_once(__DIR__ . '/purchase_orders_table.php'); ?>

    </div>
</div>