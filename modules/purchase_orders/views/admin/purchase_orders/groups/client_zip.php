<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!-- Zip purchase_orders -->
<div class="modal fade" id="client_zip_purchase_orders" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?php echo form_open('purchase_orders/zip_purchase_orders/' . $client->userid); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"><?php echo _l('client_zip_purchase_orders'); ?></h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="purchase_order_zip_status"><?php echo _l('client_zip_status'); ?></label>
                            <div class="radio radio-primary">
                                <input type="radio" value="all" id="all" checked name="purchase_order_zip_status">
                                <label for="all"><?php echo _l('client_zip_status_all'); ?></label>
                            </div>
                            <?php foreach ($purchase_order_statuses as $status) { ?>
                            <div class="radio radio-primary">
                                <input type="radio" value="<?php echo e($status); ?>" id="est_<?php echo e($status); ?>"
                                    name="purchase_order_zip_status">
                                <label
                                    for="dlv_<?php echo e($status); ?>"><?php echo format_purchase_order_status($status, '', false); ?></label>
                            </div>
                            <?php } ?>
                        </div>
                        <?php $this->load->view('admin/clients/modals/modal_zip_date_picker'); ?>
                        <?php echo form_hidden('file_name', $zip_in_folder); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>