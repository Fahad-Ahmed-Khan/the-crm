<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div role="tabpanel" class="tab-pane" id="purchase_orders">
    <div class="form-group">
        <label class="control-label"
            for="purchase_order_prefix"><?php echo _l('settings_sales_purchase_order_prefix'); ?></label>
        <input type="text" name="settings[purchase_order_prefix]" class="form-control"
            value="<?php echo get_option('purchase_order_prefix'); ?>">
    </div>
    <hr />
    <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip"
        data-title="<?php echo _l('settings_sales_next_purchase_order_number_tooltip'); ?>"></i>
    <?php echo render_input('settings[next_purchase_order_number]', 'settings_sales_next_purchase_order_number', get_option('next_purchase_order_number'), 'number', ['min' => 1]); ?>
    <hr />

    <?php render_yes_no_option('delete_only_on_last_purchase_order', 'settings_delete_only_on_last_purchase_order'); ?>
    <hr />
    <?php render_yes_no_option('purchase_order_number_decrement_on_delete', 'settings_sales_decrement_purchase_order_number_on_delete', 'settings_sales_decrement_purchase_order_number_on_delete_tooltip'); ?>
    <hr />
    <?php echo render_yes_no_option('allow_staff_view_purchase_orders_assigned', 'allow_staff_view_purchase_orders_assigned'); ?>
    <hr />

    <?php render_yes_no_option('view_purchase_order_only_logged_in', 'settings_sales_require_client_logged_in_to_view_purchase_order'); ?>
    <hr />
    <?php render_yes_no_option('show_sale_agent_on_purchase_orders', 'settings_show_sale_agent_on_purchase_orders'); ?>
    <hr />
    <?php render_yes_no_option('show_project_on_purchase_order', 'show_project_on_purchase_order'); ?>
    <hr />
    <?php render_yes_no_option('show_purchase_order_status_widget_on_dashboard', 'show_purchase_order_status_widget_on_dashboard'); ?>
    <hr />
    <?php render_yes_no_option('purchase_order_auto_convert_to_invoice_on_staff_confirm', 'settings_purchase_order_auto_convert_to_invoice_on_staff_confirm'); ?>
    <hr />
    <?php render_yes_no_option('exclude_purchase_order_from_client_area_with_new_status', 'settings_exclude_purchase_order_from_client_area_with_new_status'); ?>
    <hr />
    <div class="form-group">
        <label for="purchase_order_number_format"
            class="control-label clearfix"><?php echo _l('settings_sales_purchase_order_number_format'); ?></label>
        <div class="radio radio-primary radio-inline">
            <input type="radio" name="settings[purchase_order_number_format]" value="1" id="e_number_based" <?php if (get_option('purchase_order_number_format') == '1') {
                                                                                                                echo 'checked';
                                                                                                            } ?>>
            <label
                for="e_number_based"><?php echo _l('settings_sales_purchase_order_number_format_number_based'); ?></label>
        </div>
        <div class="radio radio-primary radio-inline">
            <input type="radio" name="settings[purchase_order_number_format]" value="2" id="e_year_based" <?php if (get_option('purchase_order_number_format') == '2') {
                                                                                                                echo 'checked';
                                                                                                            } ?>>
            <label for="e_year_based"><?php echo _l('settings_sales_purchase_order_number_format_year_based'); ?>
                (YYYY/000001)</label>
        </div>
        <div class="radio radio-primary radio-inline">
            <input type="radio" name="settings[purchase_order_number_format]" value="3" id="e_short_year_based" <?php if (get_option('purchase_order_number_format') == '3') {
                                                                                                                    echo 'checked';
                                                                                                                } ?>>
            <label for="e_short_year_based">000001-YY</label>
        </div>
        <div class="radio radio-primary radio-inline">
            <input type="radio" name="settings[purchase_order_number_format]" value="4" id="e_year_month_based" <?php if (get_option('purchase_order_number_format') == '4') {
                                                                                                                    echo 'checked';
                                                                                                                } ?>>
            <label for="e_year_month_based">000001/MM/YYYY</label>
        </div>
        <hr />
    </div>
    <div class="row">
        <div class="col-md-12">
            <?php echo render_input('settings[purchase_orders_pipeline_limit]', 'pipeline_limit_status', get_option('purchase_orders_pipeline_limit')); ?>
        </div>
        <div class="col-md-7">
            <label for="default_proposals_pipeline_sort"
                class="control-label"><?php echo _l('default_pipeline_sort'); ?></label>
            <select name="settings[default_purchase_orders_pipeline_sort]" id="default_purchase_orders_pipeline_sort"
                class="selectpicker" data-width="100%"
                data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                <option value="datecreated" <?php if (get_option('default_purchase_orders_pipeline_sort') == 'datecreated') {
                                                echo 'selected';
                                            } ?>><?php echo _l('purchase_orders_sort_datecreated'); ?></option>
                <option value="date" <?php if (get_option('default_purchase_orders_pipeline_sort') == 'date') {
                                            echo 'selected';
                                        } ?>><?php echo _l('purchase_orders_sort_purchase_order_date'); ?></option>
                <option value="pipeline_order" <?php if (get_option('default_purchase_orders_pipeline_sort') == 'pipeline_order') {
                                                    echo 'selected';
                                                } ?>><?php echo _l('purchase_orders_sort_pipeline'); ?></option>
            </select>
        </div>
        <div class="col-md-5">
            <div class="mtop30 text-right">
                <div class="radio radio-inline radio-primary">
                    <input type="radio" id="k_desc_purchase_order"
                        name="settings[default_purchase_orders_pipeline_sort_type]" value="asc"
                        <?php if (get_option('default_purchase_orders_pipeline_sort_type') == 'asc') {
                                                                                                                                                echo 'checked';
                                                                                                                                            } ?>>
                    <label for="k_desc_purchase_order"><?php echo _l('order_ascending'); ?></label>
                </div>
                <div class="radio radio-inline radio-primary">
                    <input type="radio" id="k_asc_purchase_order"
                        name="settings[default_purchase_orders_pipeline_sort_type]" value="desc"
                        <?php if (get_option('default_purchase_orders_pipeline_sort_type') == 'desc') {
                                                                                                                                                echo 'checked';
                                                                                                                                            } ?>>
                    <label for="k_asc_purchase_order"><?php echo _l('order_descending'); ?></label>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
    <hr />
    <?php
    $conversion_options = ['estimate' => 'purchase_order_allow_creating_from_estimate'];
    foreach ($conversion_options as $type => $key) {
        render_yes_no_option($key, _l('purchase_order_allow_creating_from', _l($type, '', false)));
    } ?>
    <hr />
    <?php
    $conversion_options = ['invoice' => 'purchase_order_allow_convert_to_invoice'];
    foreach ($conversion_options as $type => $key) {
        render_yes_no_option($key, _l('purchase_order_allow_converting_to', _l($type, '', false)));
    }
    ?>
    <hr />
    <?php $key = 'purchase_order_allow_transfer_of_non_similar_custom_fields'; ?>
    <?php render_yes_no_option($key, $key, $key . '_hint'); ?>
    <hr />
    <?php echo render_textarea('settings[predefined_clientnote_purchase_order]', 'settings_predefined_clientnote', get_option('predefined_clientnote_purchase_order'), ['rows' => 6]); ?>
    <?php echo render_textarea('settings[predefined_terms_purchase_order]', 'settings_predefined_predefined_term', get_option('predefined_terms_purchase_order'), ['rows' => 6]); ?>
</div>