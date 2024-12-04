<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-header">
 <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
 <h4 class="modal-title">
  Add Remark
</h4>
</div>
<?php echo form_open(admin_url('lead_manager/zoom_meeting/update_meeting_remark/'.$meeting_id),array('id'=>'meeting_remark_form','autocomplete'=>'off')); ?>
<?php echo render_input('meeting_id','',$meeting_id,'hidden'); ?>

<div class="modal-body">
  <div class="row">
    <div class="col-md-12">
      <?= render_textarea('remark',_l('lead_manager_message_data'),'',['required'=>'required']); ?>
    </div>
  </div>
</div>

<div class="modal-footer">
  <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
  <button type="submit" class="btn btn-info " data-lead = "<?= $meeting_id;?>" data-loading-text="<?php echo _l('wait_text'); ?>" autocomplete="off" data-form="#meeting_remark_form"><?php echo _l('save'); ?></button>
</div>
<?php echo form_close(); ?>
