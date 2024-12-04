<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="col-md-12">
    <?php $this->load->view('admin/purchase_orders/purchase_orders_top_stats');
    ?>
    <?php if (has_permission('purchase_orders', '', 'create')) { ?>
    <a href="<?php echo admin_url('purchase_orders/purchase_order'); ?>"
        class="btn btn-primary pull-left new new-purchase_order-btn">
        <i class="fa-regular fa-plus tw-mr-1"></i>
        <?php echo _l('create_new_purchase_order'); ?>
    </a>
    <?php } ?>
    <a href="<?php echo admin_url('purchase_orders/pipeline/' . $switch_pipeline); ?>"
        class="btn btn-default mleft5 pull-left switch-pipeline hidden-xs" data-toggle="tooltip" data-placement="top"
        data-title="<?php echo _l('switch_to_pipeline'); ?>">
        <i class="fa-solid fa-grip-vertical"></i>
    </a>
    <div class="display-block text-right">
        <div class="btn-group pull-right mleft4 btn-with-tooltip-group _filter_data" data-toggle="tooltip"
            data-title="<?php echo _l('filter_by'); ?>">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
                aria-expanded="false">
                <i class="fa fa-filter" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu width300">
                <li>
                    <a href="#" data-cview="all"
                        onclick="dt_custom_view('','.table-purchase_orders',''); return false;">
                        <?php echo _l('purchase_orders_list_all'); ?>
                    </a>
                </li>
                <li class="divider"></li>
                <li class="<?php if ($this->input->get('filter') == 'not_sent') {
                                echo 'active';
                            } ?>">
                    <a href="#" data-cview="not_sent"
                        onclick="dt_custom_view('not_sent','.table-purchase_orders','not_sent'); return false;">
                        <?php echo _l('not_sent_indicator'); ?>
                    </a>
                </li>
                <li>
                    <a href="#" data-cview="invoiced"
                        onclick="dt_custom_view('invoiced','.table-purchase_orders','invoiced'); return false;">
                        <?php echo _l('purchase_order_invoiced'); ?>
                    </a>
                </li>
                <li>
                    <a href="#" data-cview="not_invoiced"
                        onclick="dt_custom_view('not_invoiced','.table-purchase_orders','not_invoiced'); return false;"><?php echo _l('purchase_orders_not_invoiced'); ?></a>
                </li>
                <li class="divider"></li>
                <?php foreach ($purchase_order_statuses as $status) { ?>
                <li class="<?php if ($this->input->get('status') == $status) {
                                    echo 'active';
                                } ?>">
                    <a href="#" data-cview="purchase_orders_<?php echo $status; ?>"
                        onclick="dt_custom_view('purchase_orders_<?php echo $status; ?>','.table-purchase_orders','purchase_orders_<?php echo $status; ?>'); return false;">
                        <?php echo format_purchase_order_status($status, '', false); ?>
                    </a>
                </li>
                <?php } ?>
                <div class="clearfix"></div>

                <?php if (count($purchase_orders_sale_agents) > 0) { ?>
                <div class="clearfix"></div>
                <li class="divider"></li>
                <li class="dropdown-submenu pull-left">
                    <a href="#" tabindex="-1"><?php echo _l('sale_agent_string'); ?></a>
                    <ul class="dropdown-menu dropdown-menu-left">
                        <?php foreach ($purchase_orders_sale_agents as $agent) { ?>
                        <li>
                            <a href="#" data-cview="sale_agent_<?php echo $agent['sale_agent']; ?>"
                                onclick="dt_custom_view(<?php echo $agent['sale_agent']; ?>,'.table-purchase_orders','sale_agent_<?php echo $agent['sale_agent']; ?>'); return false;"><?php echo $agent['full_name']; ?>
                            </a>
                        </li>
                        <?php } ?>
                    </ul>
                </li>
                <?php } ?>
                <div class="clearfix"></div>
                <?php if (count($purchase_orders_years) > 0) { ?>
                <li class="divider"></li>
                <?php foreach ($purchase_orders_years as $year) { ?>
                <li class="active">
                    <a href="#" data-cview="year_<?php echo $year['year']; ?>"
                        onclick="dt_custom_view(<?php echo $year['year']; ?>,'.table-purchase_orders','year_<?php echo $year['year']; ?>'); return false;"><?php echo $year['year']; ?>
                    </a>
                </li>
                <?php } ?>
                <?php } ?>
            </ul>
        </div>
        <a href="#" class="btn btn-default btn-with-tooltip toggle-small-view hidden-xs"
            onclick="toggle_small_view('.table-purchase_orders','#purchase_order'); return false;" data-toggle="tooltip"
            title="<?php echo _l('purchase_orders_toggle_table_tooltip'); ?>"><i
                class="fa fa-angle-double-left"></i></a>
        <a href="#" class="btn btn-default btn-with-tooltip purchase_orders-total"
            onclick="slideToggle('#stats-top'); init_purchase_orders_total(true); return false;" data-toggle="tooltip"
            title="<?php echo _l('view_stats_tooltip'); ?>"><i class="fa fa-bar-chart"></i></a>
    </div>
    <div class="row tw-mt-2 sm:tw-mt-4">
        <div class="col-md-12" id="small-table">
            <div class="panel_s">
                <div class="panel-body">
                    <!-- if purchase_orderid found in url -->
                    <?php echo form_hidden('purchase_orderid', $purchase_orderid); ?>
                    <?php $this->load->view('admin/purchase_orders/table_html'); ?>
                </div>
            </div>
        </div>
        <div class="col-md-7 small-table-right-col">
            <div id="purchase_order" class="hide">
            </div>
        </div>
    </div>
</div>