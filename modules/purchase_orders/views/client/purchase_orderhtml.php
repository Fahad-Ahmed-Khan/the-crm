<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="mtop15 preview-top-wrapper">
    <div class="row">
        <div class="col-md-3">
            <div class="mbot30">
                <div class="purchase-orderhtml-logo">
                    <?php echo get_dark_company_logo(); ?>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
    <div class="top" data-sticky data-sticky-class="preview-sticky-header">
        <div class="container preview-sticky-container">
            <div class="sm:tw-flex sm:tw-justify-between -tw-mx-4">
                <div class="sm:tw-self-end">
                    <h3 class="bold tw-my-0 purchase-orderhtml-number">
                        <span class="sticky-visible hide tw-mb-2">
                            <?php echo format_purchase_order_number($purchase_order->id); ?>
                        </span>
                    </h3>
                    <span class="purchase-orderhtml-status">
                        <?php echo format_purchase_order_status($purchase_order->status, '', true); ?>
                    </span>
                </div>

                <div class="tw-flex tw-items-end tw-space-x-2 tw-mt-3 sm:tw-mt-0">
                    <?php echo form_open($this->uri->uri_string(), ['class' => 'action-button']); ?>
                    <button type="submit" name="purchase_orderpdf" class="btn btn-default action-button download" value="purchase_orderpdf">
                        <i class="fa-regular fa-file-pdf"></i>
                        <?php echo _l('clients_invoice_html_btn_download'); ?>
                    </button>
                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="clearfix"></div>
    <div class="panel_s tw-mt-6">
        <div class="panel-body">
            <div class="col-md-10 col-md-offset-1">
                <div class="row mtop20">
                    <div class="col-md-6 col-sm-6 transaction-html-info-col-left">
                        <h4 class="bold purchase-orderhtml-number">
                            <?php echo format_purchase_order_number($purchase_order->id); ?></h4>
                        <address class="purchase-orderhtml-company-info tw-text-neutral-500 tw-text-normal">
                            <?php echo format_organization_info(); ?>
                        </address>
                    </div>
                    <div class="col-sm-6 text-right transaction-html-info-col-right">
                        <span class="tw-font-medium tw-text-neutral-600 purchase_order_to">
                            <?php echo _l('purchase_order_to'); ?>
                        </span>
                        <address class="purchase-orderhtml-customer-billing-info tw-text-neutral-500 tw-text-normal">
                            <?php echo format_customer_info($purchase_order, 'purchase_order', 'billing'); ?>
                        </address>
                        <!-- shipping details -->
                        <?php if ($purchase_order->include_shipping == 1 && $purchase_order->show_shipping_on_purchase_order == 1) { ?>
                            <span class="tw-font-medium tw-text-neutral-700 purchase_order_ship_to">
                                <?php echo _l('ship_to'); ?>
                            </span>
                            <address class="purchase-orderhtml-customer-shipping-info tw-text-neutral-500 tw-text-normal">
                                <?php echo format_customer_info($purchase_order, 'purchase_order', 'shipping'); ?>
                            </address>
                        <?php } ?>
                        <p class="purchase-orderhtml-date tw-mb-0 tw-text-normal">
                            <span class="tw-font-medium tw-text-neutral-700">
                                <?php echo _l('purchase_order_data_date'); ?>:
                            </span>
                            <?php echo _d($purchase_order->date); ?>
                        </p>
                        <?php if (!empty($purchase_order->reference_no)) { ?>
                            <p class="purchase-orderhtml-reference-no tw-mb-0 tw-text-normal">
                                <span class="tw-font-medium tw-text-neutral-700"><?php echo _l('reference_no'); ?>:</span>
                                <?php echo $purchase_order->reference_no; ?>
                            </p>
                        <?php } ?>
                        <?php if ($purchase_order->sale_agent && get_option('show_sale_agent_on_purchase_orders') == 1) { ?>
                            <p class="purchase-orderhtml-sale-agent tw-mb-0 tw-text-normal">
                                <span class="tw-font-medium tw-text-neutral-700"><?php echo _l('sale_agent_string'); ?>:</span>
                                <?php echo get_staff_full_name($purchase_order->sale_agent); ?>
                            </p>
                        <?php } ?>
                        <?php if ($purchase_order->project_id && get_option('show_project_on_purchase_order') == 1) { ?>
                            <p class="purchase-orderhtml-project tw-mb-0 tw-text-normal">
                                <span class="tw-font-medium tw-text-neutral-700"><?php echo _l('project'); ?>:</span>
                                <?php echo get_project_name_by_id($purchase_order->project_id); ?>
                            </p>
                        <?php } ?>
                        <?php $pdf_custom_fields = get_custom_fields('purchase_order', ['show_on_pdf' => 1, 'show_on_client_portal' => 1]);
                        foreach ($pdf_custom_fields as $field) {
                            $value = get_custom_field_value($purchase_order->id, $field['id'], 'purchase_order');
                            if ($value == '') {
                                continue;
                            } ?>
                            <p class="tw-mb-0 tw-text-normal">
                                <span class="tw-font-medium tw-text-neutral-700">
                                    <?php echo $field['name']; ?>:
                                </span>
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
                            $items = get_items_table_data($purchase_order, 'purchase_order');
                            echo $items->table();
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6 col-md-offset-6">
                        <table class="table text-right tw-text-normal">
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
                    <?php
                    if (get_option('total_to_words_enabled') == 1) { ?>
                        <div class="col-md-12 text-center purchase-orderhtml-total-to-words">
                            <p class="tw-font-medium">
                                <?php echo _l('num_word'); ?>:<span class="tw-text-neutral-500">
                                    <?php echo $this->numberword->convert($purchase_order->total, $purchase_order->currency_name); ?>
                                </span>
                            </p>
                        </div>
                    <?php } ?>
                    <?php if (count($purchase_order->attachments) > 0 && $purchase_order->visible_attachments_to_customer_found == true) { ?>
                        <div class="clearfix"></div>
                        <div class="purchase-orderhtml-files">
                            <div class="col-md-12">
                                <hr />
                                <p class="bold mbot15 font-medium"><?php echo _l('purchase_order_files'); ?></p>
                            </div>
                            <?php foreach ($purchase_order->attachments as $attachment) {
                                // Do not show hidden attachments to customer
                                if ($attachment['visible_to_customer'] == 0) {
                                    continue;
                                }
                                $attachment_url = site_url('download/file/sales_attachment/' . $attachment['attachment_key']);
                                if (!empty($attachment['external'])) {
                                    $attachment_url = $attachment['external_link'];
                                } ?>
                                <div class="col-md-12 mbot15">
                                    <div class="pull-left"><i class="<?php echo get_mime_class($attachment['filetype']); ?>"></i>
                                    </div>
                                    <a href="<?php echo $attachment_url; ?>"><?php echo $attachment['file_name']; ?></a>
                                </div>
                            <?php
                            } ?>
                        </div>
                    <?php } ?>
                    <?php if (!empty($purchase_order->clientnote)) { ?>
                        <div class="col-md-12 purchase-orderhtml-note">
                            <p class="tw-mb-2.5 tw-font-medium">
                                <b><?php echo _l('purchase_order_note'); ?></b>
                            </p>
                            <div class="tw-text-neutral-500">
                                <?php echo $purchase_order->clientnote; ?>
                            </div>
                        </div>
                    <?php } ?>
                    <?php if (!empty($purchase_order->terms)) { ?>
                        <div class="col-md-12 purchase-orderhtml-terms-and-conditions">
                            <hr />
                            <p class="tw-mb-2.5 tw-font-medium">
                                <b><?php echo _l('terms_and_conditions'); ?></b>
                            </p>
                            <div class="tw-text-neutral-500">
                                <?php echo $purchase_order->terms; ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    if ($identity_confirmation_enabled == '1' && $can_be_accepted) {
        get_template_part('identity_confirmation_form', ['formData' => form_hidden('purchase_order_action', 4)]);
    }
    ?>
    <script>
        $(function() {
            new Sticky('[data-sticky]');
        })
    </script>