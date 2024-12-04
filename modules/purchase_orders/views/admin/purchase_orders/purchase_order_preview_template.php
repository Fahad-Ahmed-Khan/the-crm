<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo form_hidden('_attachment_sale_id', $purchase_order->id); ?>
<?php echo form_hidden('_attachment_sale_type', 'purchase_order'); ?>
<div class="col-md-12 no-padding">
    <div class="panel_s">
        <div class="panel-body">
            <div class="horizontal-scrollable-tabs preview-tabs-top panel-full-width-tabs">
                <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
                <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
                <div class="horizontal-tabs">
                    <ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
                        <li role="presentation" class="active">
                            <a href="#tab_purchase_order" aria-controls="tab_purchase_order" role="tab" data-toggle="tab">
                                <?php echo _l('purchase_order'); ?>
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#tab_tasks" onclick="init_rel_tasks_table(<?php echo $purchase_order->id; ?>,'purchase_order'); return false;" aria-controls="tab_tasks" role="tab" data-toggle="tab">
                                <?php echo _l('tasks'); ?>
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#tab_activity" aria-controls="tab_activity" role="tab" data-toggle="tab">
                                <?php echo _l('purchase_order_view_activity_tooltip'); ?>
                            </a>
                        </li>
                        <li role="presentation">
                            <a href="#tab_reminders" onclick="initDataTable('.table-reminders', admin_url + 'misc/get_reminders/' + <?php echo $purchase_order->id; ?> + '/' + 'purchase_order', undefined, undefined, undefined,[1,'asc']); return false;" aria-controls="tab_reminders" role="tab" data-toggle="tab">
                                <?php echo _l('purchase_order_reminders'); ?>
                                <?php
                                $total_reminders = total_rows(
                                    db_prefix() . 'reminders',
                                    [
                                        'isnotified' => 0,
                                        'staff'      => get_staff_user_id(),
                                        'rel_type'   => 'purchase_order',
                                        'rel_id'     => $purchase_order->id,
                                    ]
                                );
                                if ($total_reminders > 0) {
                                    echo '<span class="badge">' . $total_reminders . '</span>';
                                }
                                ?>
                            </a>
                        </li>
                        <li role="presentation" class="tab-separator">
                            <a href="#tab_notes" onclick="get_sales_notes(<?php echo $purchase_order->id; ?>,'purchase_orders'); return false" aria-controls="tab_notes" role="tab" data-toggle="tab">
                                <?php echo _l('purchase_order_notes'); ?>
                                <span class="notes-total">
                                    <?php if ($totalNotes > 0) { ?>
                                        <span class="badge"><?php echo $totalNotes; ?></span>
                                    <?php } ?>
                                </span>
                            </a>
                        </li>
                        <li role="presentation" data-toggle="tooltip" title="<?php echo _l('emails_tracking'); ?>" class="tab-separator">
                            <a href="#tab_emails_tracking" aria-controls="tab_emails_tracking" role="tab" data-toggle="tab">
                                <?php if (!is_mobile()) { ?>
                                    <i class="fa-regular fa-envelope-open" aria-hidden="true"></i>
                                <?php } else { ?>
                                    <?php echo _l('emails_tracking'); ?>
                                <?php } ?>
                            </a>
                        </li>
                        <li role="presentation" data-toggle="tooltip" data-title="<?php echo _l('view_tracking'); ?>" class="tab-separator">
                            <a href="#tab_views" aria-controls="tab_views" role="tab" data-toggle="tab">
                                <?php if (!is_mobile()) { ?>
                                    <i class="fa fa-eye"></i>
                                <?php } else { ?>
                                    <?php echo _l('view_tracking'); ?>
                                <?php } ?>
                            </a>
                        </li>
                        <li role="presentation" data-toggle="tooltip" data-title="<?php echo _l('toggle_full_view'); ?>" class="tab-separator toggle_view">
                            <a href="#" onclick="small_table_full_view(); return false;">
                                <i class="fa fa-expand"></i></a>
                        </li>
                        <?php hooks()->do_action('after_admin_purchase_order_preview_template_tab_menu_last_item', $purchase_order); ?>
                    </ul>
                </div>
            </div>
            <div class="row mtop20">
                <div class="col-md-3">
                    <?php echo format_purchase_order_status($purchase_order->status, 'mtop5 inline-block'); ?>
                </div>
                <div class="col-md-9">
                    <div class="visible-xs">
                        <div class="mtop10"></div>
                    </div>
                    <div class="pull-right _buttons">
                        <?php if (staff_can('edit', 'purchase_orders')) { ?>
                            <a href="<?php echo admin_url('purchase_orders/purchase_order/' . $purchase_order->id); ?>" class="btn btn-default btn-with-tooltip" data-toggle="tooltip" title="<?php echo _l('edit_purchase_order_tooltip'); ?>" data-placement="bottom"><i class="fa-regular fa-pen-to-square"></i></a>
                        <?php } ?>
                        <div class="btn-group">
                            <a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa-regular fa-file-pdf"></i><?php if (is_mobile()) {
                                                                                                                                                                                            echo ' PDF';
                                                                                                                                                                                        } ?>
                                <span class="caret"></span></a>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li class="hidden-xs"><a href="<?php echo admin_url('purchase_orders/pdf/' . $purchase_order->id . '?output_type=I'); ?>"><?php echo _l('view_pdf'); ?></a>
                                </li>
                                <li class="hidden-xs"><a href="<?php echo admin_url('purchase_orders/pdf/' . $purchase_order->id . '?output_type=I'); ?>" target="_blank"><?php echo _l('view_pdf_in_new_window'); ?></a></li>
                                <li><a href="<?php echo admin_url('purchase_orders/pdf/' . $purchase_order->id); ?>"><?php echo _l('download'); ?></a>
                                </li>
                                <li>
                                    <a href="<?php echo admin_url('purchase_orders/pdf/' . $purchase_order->id . '?print=true'); ?>" target="_blank">
                                        <?php echo _l('print'); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <?php
                        $_tooltip              = _l('purchase_order_sent_to_email_tooltip');
                        $_tooltip_already_send = '';
                        if ($purchase_order->sent == 1) {
                            $_tooltip_already_send = _l('purchase_order_already_send_to_client_tooltip', time_ago($purchase_order->datesend));
                        }
                        ?>
                        <?php if (!empty($purchase_order->clientid)) { ?>
                            <a href="#" class="purchase-order-send-to-client btn btn-default btn-with-tooltip" data-toggle="tooltip" title="<?php echo $_tooltip; ?>" data-placement="bottom"><span data-toggle="tooltip" data-title="<?php echo $_tooltip_already_send; ?>"><i class="fa-regular fa-envelope"></i></span></a>
                        <?php } ?>
                        <div class="btn-group">
                            <button type="button" class="btn btn-default pull-left dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <?php echo _l('more'); ?> <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li>
                                    <a href="<?php echo site_url('purchase_orders/client/po/' . $purchase_order->id . '/' . $purchase_order->hash) ?>" target="_blank">
                                        <?php echo _l('view_purchase_order_as_client'); ?>
                                    </a>
                                </li>
                                <?php hooks()->do_action('after_purchase_order_view_as_client_link', $purchase_order); ?>
                                <li>
                                    <a href="#" data-toggle="modal" data-target="#sales_attach_file"><?php echo _l('invoice_attach_file'); ?></a>
                                </li>

                                <?php if ($purchase_order->invoiceid == null) {
                                    if (staff_can('edit', 'purchase_orders')) {
                                        foreach ($purchase_order_statuses as $status) {
                                            if ($purchase_order->status != $status) { ?>
                                                <li>
                                                    <a href="<?php echo admin_url() . 'purchase_orders/mark_action_status/' . $status . '/' . $purchase_order->id; ?>">
                                                        <?php echo _l('purchase_order_mark_as', format_purchase_order_status($status, '', false)); ?></a>
                                                </li>
                                        <?php }
                                        } ?>
                                    <?php
                                    } ?>
                                <?php
                                } ?>
                                <?php if (staff_can('create', 'purchase_orders')) { ?>
                                    <li>
                                        <a href="<?php echo admin_url('purchase_orders/copy/' . $purchase_order->id); ?>">
                                            <?php echo _l('copy_purchase_order'); ?>
                                        </a>
                                    </li>
                                <?php } ?>

                                <?php if (staff_can('delete', 'purchase_orders')) { ?>
                                    <?php
                                    if ((get_option('delete_only_on_last_purchase_order') == 1 && is_last_purchase_order($purchase_order->id)) || (get_option('delete_only_on_last_purchase_order') == 0)) { ?>
                                        <li>
                                            <a href="<?php echo admin_url('purchase_orders/delete/' . $purchase_order->id); ?>" class="text-danger delete-text _delete"><?php echo _l('delete_purchase_order_tooltip'); ?></a>
                                        </li>
                                <?php
                                    }
                                }
                                ?>
                            </ul>
                        </div>

                        <!-- conversion history -->
                        <?php if (isset($purchase_order->estimate->id)) { ?>
                            <a data-placement="bottom" data-toggle="tooltip" title="<?php echo _l('purchase_order_created_from_date', [_l('estimate'), _dt($purchase_order->datecreated)]); ?>" href="<?php echo admin_url('estimates/list_estimates/' . $purchase_order->estimate->id); ?>" class="btn btn-primary mleft10 pull-right"><?php echo format_estimate_number($purchase_order->estimate); ?></a>
                        <?php } ?>

                        <!-- conversion to another resourcs -->
                        <?php if ($purchase_order->invoiceid == null || !isset($purchase_order->invoice->id)) { ?>
                            <?php if (staff_can('create', 'invoices') && !empty($purchase_order->clientid)) { ?>
                                <div class="btn-group pull-right mleft5">
                                    <?php if ((int)get_option('purchase_order_allow_convert_to_invoice')) { ?>
                                        <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <?php echo _l('purchase_order_convert_to_invoice'); ?> <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a href="<?php echo admin_url('purchase_orders/convert_to_invoice/' . $purchase_order->id . '?save_as_draft=true'); ?>"><?php echo _l('convert_and_save_as_draft'); ?></a>
                                            </li>
                                            <li class="divider">
                                            <li><a href="<?php echo admin_url('purchase_orders/convert_to_invoice/' . $purchase_order->id); ?>"><?php echo _l('convert'); ?></a>
                                            </li>
                                            </li>
                                        </ul>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <a href="<?php echo admin_url('invoices/list_invoices/' . $purchase_order->invoice->id); ?>" data-placement="bottom" data-toggle="tooltip" title="<?php echo _l('purchase_order_invoiced_date', _dt($purchase_order->invoiced_date)); ?>" class="btn btn-primary mleft10 pull-right"><?php echo format_invoice_number($purchase_order->invoice->id); ?></a>
                        <?php } ?>

                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
            <hr class="hr-panel-separator" />
            <div class="tab-content">
                <div role="tabpanel" class="tab-pane ptop10 active" id="tab_purchase_order">
                    <?php if (isset($purchase_order->scheduled_email) && $purchase_order->scheduled_email) { ?>
                        <div class="alert alert-warning">
                            <?php echo _l('invoice_will_be_sent_at', _dt($purchase_order->scheduled_email->scheduled_at)); ?>
                            <?php if (staff_can('edit', 'purchase_orders') || $purchase_order->addedfrom == get_staff_user_id()) { ?>
                                <a href="#" onclick="edit_purchase_order_scheduled_email(<?php echo $purchase_order->scheduled_email->id; ?>); return false;">
                                    <?php echo _l('edit'); ?>
                                </a>
                            <?php } ?>
                        </div>
                    <?php } ?>
                    <div id="purchase_order-preview">
                        <div class="row">
                            <?php if ($purchase_order->status == 4 && !empty($purchase_order->acceptance_firstname) && !empty($purchase_order->acceptance_lastname) && !empty($purchase_order->acceptance_email)) { ?>
                                <div class="col-md-12">
                                    <div class="alert alert-info mbot15">
                                        <?php echo _l('accepted_identity_info', [
                                            _l('purchase_order_lowercase'),
                                            '<b>' . $purchase_order->acceptance_firstname . ' ' . $purchase_order->acceptance_lastname . '</b> (<a href="mailto:' . $purchase_order->acceptance_email . '">' . $purchase_order->acceptance_email . '</a>)',
                                            '<b>' . _dt($purchase_order->acceptance_date) . '</b>',
                                            '<b>' . $purchase_order->acceptance_ip . '</b>' . (is_admin() ? '&nbsp;<a href="' . admin_url('purchase_orders/clear_acceptance_info/' . $purchase_order->id) . '" class="_delete text-muted" data-toggle="tooltip" data-title="' . _l('clear_this_information') . '"><i class="fa fa-remove"></i></a>' : ''),
                                        ]); ?>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if ($purchase_order->project_id) { ?>
                                <div class="col-md-12">
                                    <h4 class="font-medium mbot15"><?php echo _l('related_to_project', [
                                                                        _l('purchase_order_lowercase'),
                                                                        _l('project_lowercase'),
                                                                        '<a href="' . admin_url('projects/view/' . $purchase_order->project_id) . '" target="_blank">' . $purchase_order->project_data->name . '</a>',
                                                                    ]); ?></h4>
                                </div>
                            <?php } ?>
                            <div class="col-md-6 col-sm-6">
                                <h4 class="bold">
                                    <?php
                                    $tags = get_tags_in($purchase_order->id, 'purchase_order');
                                    if (count($tags) > 0) {
                                        echo '<i class="fa fa-tag" aria-hidden="true" data-toggle="tooltip" data-title="' . html_escape(implode(', ', $tags)) . '"></i>';
                                    }
                                    ?>
                                    <a href="<?php echo admin_url('purchase_orders/purchase_order/' . $purchase_order->id); ?>">
                                        <span id="purchase_order-number">
                                            <?php echo format_purchase_order_number($purchase_order->id); ?>
                                        </span>
                                    </a>
                                </h4>
                                <address class="tw-text-neutral-500">
                                    <?php echo format_organization_info(); ?>
                                </address>
                            </div>
                            <div class="col-sm-6 text-right">
                                <span class="bold"><?php echo _l('purchase_order_to'); ?></span>
                                <address class="tw-text-neutral-500">
                                    <?php echo format_customer_info($purchase_order, 'purchase_order', 'billing', true); ?>
                                </address>
                                <?php if ($purchase_order->include_shipping == 1 && $purchase_order->show_shipping_on_purchase_order == 1) { ?>
                                    <span class="bold"><?php echo _l('ship_to'); ?></span>
                                    <address class="tw-text-neutral-500">
                                        <?php echo format_customer_info($purchase_order, 'purchase_order', 'shipping'); ?>
                                    </address>
                                <?php } ?>
                                <p class="no-mbot">
                                    <span class="bold">
                                        <?php echo _l('purchase_order_data_date'); ?>:
                                    </span>
                                    <?php echo $purchase_order->date; ?>
                                </p>

                                <?php if (!empty($purchase_order->reference_no)) { ?>
                                    <p class="no-mbot">
                                        <span class="bold"><?php echo _l('reference_no'); ?>:</span>
                                        <?php echo $purchase_order->reference_no; ?>
                                    </p>
                                <?php } ?>
                                <?php if ($purchase_order->sale_agent && get_option('show_sale_agent_on_purchase_orders') == 1) { ?>
                                    <p class="no-mbot">
                                        <span class="bold"><?php echo _l('sale_agent_string'); ?>:</span>
                                        <?php echo get_staff_full_name($purchase_order->sale_agent); ?>
                                    </p>
                                <?php } ?>
                                <?php if ($purchase_order->project_id && get_option('show_project_on_purchase_order') == 1) { ?>
                                    <p class="no-mbot">
                                        <span class="bold"><?php echo _l('project'); ?>:</span>
                                        <?php echo get_project_name_by_id($purchase_order->project_id); ?>
                                    </p>
                                <?php } ?>
                                <?php $pdf_custom_fields = get_custom_fields('purchase_order', ['show_on_pdf' => 1]);
                                foreach ($pdf_custom_fields as $field) {
                                    $value = get_custom_field_value($purchase_order->id, $field['id'], 'purchase_order');
                                    if ($value == '') {
                                        continue;
                                    } ?>
                                    <p class="no-mbot">
                                        <span class="bold"><?php echo $field['name']; ?>: </span>
                                        <?php echo $value; ?>
                                    </p>
                                <?php
                                } ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <?php
                                    $items = get_items_table_data($purchase_order, 'purchase_order', 'html', true);
                                    echo $items->table();
                                    ?>
                                </div>
                            </div>
                            <div class="col-md-5 col-md-offset-7">
                                <table class="table text-right">
                                    <tbody>
                                        <tr id="subtotal">
                                            <td>
                                                <span class="bold tw-text-neutral-700">
                                                    <?php echo _l('purchase_order_subtotal'); ?>
                                                </span>
                                            </td>
                                            <td class="subtotal">
                                                <?php echo app_format_money($purchase_order->subtotal, $purchase_order->currency_name); ?>
                                            </td>
                                        </tr>
                                        <?php if (is_sale_discount_applied($purchase_order)) { ?>
                                            <tr>
                                                <td>
                                                    <span class="bold tw-text-neutral-700"><?php echo _l('purchase_order_discount'); ?>
                                                        <?php if (is_sale_discount($purchase_order, 'percent')) { ?>
                                                            (<?php echo app_format_number($purchase_order->discount_percent, true); ?>%)
                                                        <?php } ?>
                                                    </span>
                                                </td>
                                                <td class="discount">
                                                    <?php echo '-' . app_format_money($purchase_order->discount_total, $purchase_order->currency_name); ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <?php
                                        foreach ($items->taxes() as $tax) {
                                            echo '<tr class="tax-area"><td class="bold !tw-text-neutral-700">' . $tax['taxname'] . ' (' . app_format_number($tax['taxrate']) . '%)</td><td>' . app_format_money($tax['total_tax'], $purchase_order->currency_name) . '</td></tr>';
                                        }
                                        ?>
                                        <?php if ((int)$purchase_order->adjustment != 0) { ?>
                                            <tr>
                                                <td>
                                                    <span class="bold tw-text-neutral-700">
                                                        <?php echo _l('purchase_order_adjustment'); ?>
                                                    </span>
                                                </td>
                                                <td class="adjustment">
                                                    <?php echo app_format_money($purchase_order->adjustment, $purchase_order->currency_name); ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <tr>
                                            <td>
                                                <span class="bold tw-text-neutral-700">
                                                    <?php echo _l('purchase_order_total'); ?>
                                                </span>
                                            </td>
                                            <td class="total">
                                                <?php echo app_format_money($purchase_order->total, $purchase_order->currency_name); ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (count($purchase_order->attachments) > 0) { ?>
                                <div class="clearfix"></div>
                                <hr />
                                <div class="col-md-12">
                                    <p class="bold text-muted"><?php echo _l('purchase_order_files'); ?></p>
                                </div>
                                <?php foreach ($purchase_order->attachments as $attachment) {
                                    $attachment_url = site_url('download/file/sales_attachment/' . $attachment['attachment_key']);
                                    if (!empty($attachment['external'])) {
                                        $attachment_url = $attachment['external_link'];
                                    } ?>
                                    <div class="mbot15 row col-md-12" data-attachment-id="<?php echo $attachment['id']; ?>">
                                        <div class="col-md-8">
                                            <div class="pull-left"><i class="<?php echo get_mime_class($attachment['filetype']); ?>"></i></div>
                                            <a href="<?php echo $attachment_url; ?>" target="_blank"><?php echo $attachment['file_name']; ?></a>
                                            <br />
                                            <small class="text-muted"> <?php echo $attachment['filetype']; ?></small>
                                        </div>
                                        <div class="col-md-4 text-right tw-space-x-2">
                                            <?php if ($attachment['visible_to_customer'] == 0) {
                                                $icon    = 'fa fa-toggle-off';
                                                $tooltip = _l('show_to_customer');
                                            } else {
                                                $icon    = 'fa fa-toggle-on';
                                                $tooltip = _l('hide_from_customer');
                                            } ?>
                                            <a href="#" data-toggle="tooltip" onclick="toggle_file_visibility(<?php echo $attachment['id']; ?>,<?php echo $purchase_order->id; ?>,this); return false;" data-title="<?php echo $tooltip; ?>"><i class="<?php echo $icon; ?> fa-lg" aria-hidden="true"></i></a>
                                            <?php if ($attachment['staffid'] == get_staff_user_id() || is_admin()) { ?>
                                                <a href="#" class="text-danger" onclick="delete_purchase_order_attachment(<?php echo $attachment['id']; ?>); return false;"><i class="fa fa-times fa-lg"></i></a>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php
                                } ?>
                            <?php } ?>
                            <?php if ($purchase_order->clientnote != '') { ?>
                                <div class="col-md-12 mtop15">
                                    <p class="bold text-muted"><?php echo _l('purchase_order_note'); ?></p>
                                    <p><?php echo $purchase_order->clientnote; ?></p>
                                </div>
                            <?php } ?>
                            <?php if ($purchase_order->terms != '') { ?>
                                <div class="col-md-12 mtop15">
                                    <p class="bold text-muted"><?php echo _l('terms_and_conditions'); ?></p>
                                    <p><?php echo $purchase_order->terms; ?></p>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div role="tabpanel" class="tab-pane" id="tab_tasks">
                    <?php init_relation_tasks_table(['data-new-rel-id' => $purchase_order->id, 'data-new-rel-type' => 'purchase_order'], 'tasksFilters'); ?>
                </div>
                <div role="tabpanel" class="tab-pane" id="tab_reminders">
                    <a href="#" data-toggle="modal" class="btn btn-primary" data-target=".reminder-modal-purchase_order-<?php echo $purchase_order->id; ?>"><i class="fa-regular fa-bell"></i>
                        <?php echo _l('purchase_order_set_reminder_title'); ?></a>
                    <hr />
                    <?php render_datatable([_l('reminder_description'), _l('reminder_date'), _l('reminder_staff'), _l('reminder_is_notified')], 'reminders'); ?>
                    <?php $this->load->view('admin/includes/modals/reminder', ['id' => $purchase_order->id, 'name' => 'purchase_order', 'members' => $members, 'reminder_title' => _l('purchase_order_set_reminder_title')]); ?>
                </div>
                <div role="tabpanel" class="tab-pane ptop10" id="tab_emails_tracking">
                    <?php
                    $this->load->view(
                        'admin/includes/emails_tracking',
                        [
                            'tracked_emails' => get_tracked_emails($purchase_order->id, 'purchase_order'),
                        ]
                    );
                    ?>
                </div>
                <div role="tabpanel" class="tab-pane" id="tab_notes">
                    <?php echo form_open(admin_url('purchase_orders/add_note/' . $purchase_order->id), ['id' => 'sales-notes-custom', 'class' => 'purchase_order-notes-form', 'data-controller' => 'purchase_orders']); ?>
                    <?php echo render_textarea('description'); ?>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary mtop15 mbot15"><?php echo _l('purchase_order_add_note'); ?></button>
                    </div>
                    <?php echo form_close(); ?>
                    <hr />
                    <div class="mtop20" id="sales_notes_area">
                    </div>
                </div>
                <div role="tabpanel" class="tab-pane" id="tab_activity">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="activity-feed">
                                <?php foreach ($activity as $activity) {
                                    $_custom_data = false; ?>
                                    <div class="feed-item" data-sale-activity-id="<?php echo $activity['id']; ?>">
                                        <div class="date">
                                            <span class="text-has-action" data-toggle="tooltip" data-title="<?php echo _dt($activity['date']); ?>">
                                                <?php echo time_ago($activity['date']); ?>
                                            </span>
                                        </div>
                                        <div class="text">
                                            <?php if (is_numeric($activity['staffid']) && $activity['staffid'] != 0) { ?>
                                                <a href="<?php echo admin_url('profile/' . $activity['staffid']); ?>">
                                                    <?php echo staff_profile_image($activity['staffid'], ['staff-profile-xs-image pull-left mright5']);
                                                    ?>
                                                </a>
                                            <?php } ?>
                                            <?php
                                            $additional_data = '';
                                            if (!empty($activity['additional_data'])) {
                                                $additional_data = app_unserialize($activity['additional_data']);
                                                $i               = 0;
                                                foreach ($additional_data as $data) {
                                                    if (strpos($data, '<original_status>') !== false) {
                                                        $original_status     = get_string_between($data, '<original_status>', '</original_status>');
                                                        $additional_data[$i] = format_purchase_order_status($original_status, '', false);
                                                    } elseif (strpos($data, '<new_status>') !== false) {
                                                        $new_status          = get_string_between($data, '<new_status>', '</new_status>');
                                                        $additional_data[$i] = format_purchase_order_status($new_status, '', false);
                                                    } elseif (strpos($data, '<status>') !== false) {
                                                        $status              = get_string_between($data, '<status>', '</status>');
                                                        $additional_data[$i] = format_purchase_order_status($status, '', false);
                                                    } elseif (strpos($data, '<custom_data>') !== false) {
                                                        $_custom_data = get_string_between($data, '<custom_data>', '</custom_data>');
                                                        unset($additional_data[$i]);
                                                    }
                                                    $i++;
                                                }
                                            }
                                            $_formatted_activity = _l($activity['description'], $additional_data);
                                            if ($_custom_data !== false) {
                                                $_formatted_activity .= ' - ' . $_custom_data;
                                            }
                                            if (!empty($activity['full_name'])) {
                                                $_formatted_activity = $activity['full_name'] . ' - ' . $_formatted_activity;
                                            }
                                            echo $_formatted_activity;
                                            if (is_admin()) {
                                                echo '<a href="#" class="pull-right text-danger" onclick="delete_sale_activity(' . $activity['id'] . '); return false;"><i class="fa fa-remove"></i></a>';
                                            } ?>
                                        </div>
                                    </div>
                                <?php
                                } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div role="tabpanel" class="tab-pane ptop10" id="tab_views">
                    <?php
                    $views_activity = get_views_tracking('purchase_order', $purchase_order->id);
                    if (count($views_activity) === 0) {
                        echo '<h4 class="tw-m-0 tw-text-base tw-font-medium tw-text-neutral-500">' . _l('not_viewed_yet', _l('purchase_order_lowercase')) . '</h4>';
                    }
                    foreach ($views_activity as $activity) { ?>
                        <p class="text-success no-margin">
                            <?php echo _l('view_date') . ': ' . _dt($activity['date']); ?>
                        </p>
                        <p class="text-muted">
                            <?php echo _l('view_ip') . ': ' . $activity['view_ip']; ?>
                        </p>
                        <hr />
                    <?php } ?>
                </div>
                <?php hooks()->do_action('after_admin_purchase_order_preview_template_tab_content_last_item', $purchase_order); ?>
            </div>
        </div>
    </div>
</div>
<script>
    init_items_sortable(true);
    init_btn_with_tooltips();
    init_datepicker();
    init_selectpicker();
    init_form_reminder();
    init_tabs_scrollable();
    <?php if ($send_later) { ?>
        schedule_purchase_order_send(<?php echo $purchase_order->id; ?>);
    <?php } ?>
</script>
<?php $this->load->view('admin/purchase_orders/purchase_order_send_to_client'); ?>