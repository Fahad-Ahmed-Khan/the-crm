<?php defined('BASEPATH') or exit('No direct script access allowed');
require_once(module_dir_path(PURCHASE_ORDER_MODULE_NAME, 'services/purchase_orders/PurchaseOrdersPipeline.php'));

$i                   = 0;
$has_permission_edit = staff_can('edit',  'purchase_orders');
foreach ($purchase_order_statuses as $status) {
    $kanBan = new \app\modules\purchase_orders\services\PurchaseOrderPipeline($status);
    $kanBan->search($this->input->get('search'))
        ->sortBy($this->input->get('sort_by'), $this->input->get('sort'));
    if ($this->input->get('refresh')) {
        $kanBan->refresh($this->input->get('refresh')[$status] ?? null);
    }
    $purchase_orders       = $kanBan->get();
    $total_purchase_orders = count($purchase_orders);
    $total_pages     = $kanBan->totalPages(); ?>
    <ul class="kan-ban-col" data-col-status-id="<?php echo $status; ?>" data-total-pages="<?php echo $total_pages; ?>" data-total="<?php echo $total_purchase_orders; ?>">
        <li class="kan-ban-col-wrapper">
            <div class="panel_s panel-<?php echo purchase_order_status_color_class($status); ?> no-mbot">
                <div class="panel-heading">
                    <?php echo purchase_order_status_by_id($status); ?> -
                    <span class="tw-text-sm">
                        <?php echo $kanBan->countAll() . ' ' . _l('purchase_orders') ?>
                    </span>
                </div>
                <div class="kan-ban-content-wrapper">
                    <div class="kan-ban-content">
                        <ul class="sortable<?php if ($has_permission_edit) {
                                                echo ' status pipeline-status';
                                            } ?>" data-status-id="<?php echo $status; ?>">
                            <?php
                            foreach ($purchase_orders as $purchase_order) {
                                $this->load->view('admin/purchase_orders/pipeline/_kanban_card', ['purchase_order' => $purchase_order, 'status' => $status]);
                            } ?>
                            <?php if ($total_purchase_orders > 0) { ?>
                                <li class="text-center not-sortable kanban-load-more" data-load-status="<?php echo $status; ?>">
                                    <a href="#" class="btn btn-default btn-block<?php if ($total_pages <= 1 || $kanBan->getPage() === $total_pages) {
                                                                                    echo ' disabled';
                                                                                } ?>" data-page="<?php echo $kanBan->getPage(); ?>" onclick="kanban_load_more(<?php echo $status; ?>,this,'purchase_orders/pipeline_load_more',310,360); return false;" ;><?php echo _l('load_more'); ?></a>
                                </li>
                            <?php } ?>
                            <li class="text-center not-sortable mtop30 kanban-empty<?php if ($total_purchase_orders > 0) {
                                                                                        echo ' hide';
                                                                                    } ?>">
                                <h4>
                                    <i class="fa-solid fa-circle-notch" aria-hidden="true"></i><br /><br />
                                    <?php echo _l('no_purchase_orders_found'); ?>
                                </h4>
                            </li>
                        </ul>
                    </div>
                </div>
        </li>
    </ul>
<?php $i++;
} ?>