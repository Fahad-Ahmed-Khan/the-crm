<?php defined('BASEPATH') or exit('No direct script access allowed');
if ($purchase_order['status'] == $status) { ?>
<li data-purchase_order-id="<?php echo $purchase_order['id']; ?>" class="<?php if ($purchase_order['invoiceid'] != null) {
                                                                                    echo 'not-sortable';
                                                                                } ?>">
    <div class="panel-body">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-font-semibold tw-text-base pipeline-heading tw-mb-0.5">
                    <a href="<?php echo admin_url('purchase_orders/list_purchase_orders/' . $purchase_order['id']); ?>"
                        class="tw-text-neutral-700 hover:tw-text-neutral-900 active:tw-text-neutral-900"
                        onclick="purchase_order_pipeline_open(<?php echo $purchase_order['id']; ?>); return false;">
                        <?php echo format_purchase_order_number($purchase_order['id']); ?>
                    </a>
                    <?php if (staff_can('edit',  'purchase_orders')) { ?>
                    <a href="<?php echo admin_url('purchase_orders/purchase_order/' . $purchase_order['id']); ?>"
                        target="_blank" class="pull-right">
                        <small>
                            <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                        </small>
                    </a>
                    <?php } ?>
                </h4>
                <span class="tw-inline-block tw-w-full tw-mb-2">
                    <a href="<?php echo admin_url('clients/client/' . $purchase_order['clientid']); ?>" target="_blank">
                        <?php echo $purchase_order['company']; ?>
                    </a>
                </span>
            </div>
            <div class="col-md-12">
                <div class="tw-flex">
                    <div class="tw-grow">
                        <p class="tw-mb-0 tw-text-sm tw-text-neutral-700">
                            <span class="tw-text-neutral-500">
                                <?php echo _l('purchase_order_total'); ?>:
                            </span>
                            <?php echo app_format_money($purchase_order['total'], $purchase_order['currency_name']); ?>
                        </p>
                        <p class="tw-mb-0 tw-text-sm tw-text-neutral-700">
                            <span class="tw-text-neutral-500">
                                <?php echo _l('purchase_order_data_date'); ?>:
                            </span>
                            <?php echo _d($purchase_order['date']); ?>
                        </p>

                    </div>
                    <div class="tw-shrink-0 text-right">
                        <small>
                            <i class="fa fa-paperclip"></i>
                            <?php echo _l('purchase_order_notes'); ?>:
                            <?php echo total_rows(db_prefix() . 'notes', [
                                    'rel_id'   => $purchase_order['id'],
                                    'rel_type' => 'purchase_order',
                                ]); ?>
                        </small>
                    </div>
                    <?php $tags = get_tags_in($purchase_order['id'], 'purchase_order'); ?>
                    <?php if (count($tags) > 0) { ?>
                    <div class="kanban-tags tw-text-sm tw-inline-flex">
                        <?php echo render_tags($tags); ?>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</li>
<?php } ?>