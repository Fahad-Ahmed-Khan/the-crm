<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="_buttons tw-mb-3 sm:tw-mb-5">
                    <?php if (staff_can('create',  'purchase_orders')) {
                        $this->load->view('admin/purchase_orders/purchase_orders_top_stats');
                    } ?>
                    <div class="row">
                        <div class="col-md-8">
                            <?php if (staff_can('create',  'purchase_orders')) { ?>
                            <a href="<?php echo admin_url('purchase_orders/purchase_order'); ?>"
                                class="btn btn-primary pull-left new">
                                <i class="fa-regular fa-plus tw-mr-1"></i>
                                <?php echo _l('create_new_purchase_order'); ?>
                            </a>
                            <div class="display-block pull-left mleft10">
                                <a href="#" class="btn btn-default purchase_orders-total"
                                    onclick="slideToggle('#stats-top'); init_purchase_orders_total(true); return false;"
                                    data-toggle="tooltip" title="<?php echo _l('view_stats_tooltip'); ?>"><i
                                        class="fa fa-bar-chart"></i></a>
                            </div>
                            <?php } ?>
                            <a href="<?php echo admin_url('purchase_orders/pipeline/' . $switch_pipeline); ?>"
                                class="btn btn-default mleft5 pull-left" data-toggle="tooltip" data-placement="top"
                                data-title="<?php echo _l('switch_to_list_view'); ?>">
                                <i class="fa-solid fa-table-list"></i>
                            </a>
                        </div>
                        <div class="col-md-4" data-toggle="tooltip" data-placement="top"
                            data-title="<?php echo _l('search_by_tags'); ?>">
                            <?php echo render_input('search', '', '', 'search', ['data-name' => 'search', 'onkeyup' => 'purchase_order_pipeline();', 'placeholder' => _l('search_purchase_orders')], [], 'no-margin') ?>
                            <?php echo form_hidden('sort_type'); ?>
                            <?php echo form_hidden('sort', (get_option('default_purchase_orders_pipeline_sort') != '' ? get_option('default_purchase_orders_pipeline_sort_type') : '')); ?>
                        </div>
                    </div>
                </div>
                <div class="animated mtop5 fadeIn">
                    <?php echo form_hidden('purchase_orderid', $purchase_orderid); ?>
                    <div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="kanban-leads-sort">
                                    <span class="bold"><?php echo _l('purchase_orders_pipeline_sort'); ?>: </span>
                                    <a href="#" onclick="purchase_orders_pipeline_sort('datecreated'); return false"
                                        class="datecreated">
                                        <?php if (get_option('default_purchase_orders_pipeline_sort') == 'datecreated') {
                                            echo '<i class="kanban-sort-icon fa fa-sort-amount-' . strtolower(get_option('default_purchase_orders_pipeline_sort_type')) . '"></i> ';
                                        } ?>
                                        <?php echo _l('purchase_orders_sort_datecreated'); ?>
                                    </a>
                                    |
                                    <a href="#" onclick="purchase_orders_pipeline_sort('date'); return false"
                                        class="date">
                                        <?php if (get_option('default_purchase_orders_pipeline_sort') == 'date') {
                                            echo '<i class="kanban-sort-icon fa fa-sort-amount-' . strtolower(get_option('default_purchase_orders_pipeline_sort_type')) . '"></i> ';
                                        } ?>
                                        <?php echo _l('purchase_orders_sort_purchase_order_date'); ?>
                                    </a>
                                    |
                                    <a href="#" onclick="purchase_orders_pipeline_sort('pipeline_order');return false;"
                                        class="pipeline_order">
                                        <?php if (get_option('default_purchase_orders_pipeline_sort') == 'pipeline_order') {
                                            echo '<i class="kanban-sort-icon fa fa-sort-amount-' . strtolower(get_option('default_purchase_orders_pipeline_sort_type')) . '"></i> ';
                                        } ?>
                                        <?php echo _l('purchase_orders_sort_pipeline'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div id="purchase_order-pipeline">
                                <div class="container-fluid">
                                    <div id="kan-ban"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="purchase_order">
</div>
<?php $this->load->view('admin/includes/modals/sales_attach_file'); ?>
<?php init_tail(); ?>
<script>
$(function() {
    purchase_order_pipeline();
});
</script>
</body>

</html>