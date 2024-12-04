<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-header">
 <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
 <h4 class="modal-title">
  <?php if(isset($lead)){
    if(!empty($lead->name)){
      $name = $lead->name;
    } else if(!empty($lead->company)){
      $name = $lead->company;
    } else {
      $name = _l('lead');
    }
    if(isset($is_client) && $is_client){
      echo _l('lead_manager_sms_modal_title'). $lead->phonenumber.' #'.$lead->userid . ' - ' .  $name;
    }else{
      echo _l('lead_manager_sms_modal_title'). $lead->phonenumber.' #'.$lead->id . ' - ' .  $name;
    }
  }
  ?>
</h4>
</div>
<?php 
$id = isset($is_client) && $is_client ? $lead->userid : $lead->id;
  echo form_open(admin_url('lead_manager/send_sms/'.$id),array('id'=>'sms-form-'.$id,'autocomplete'=>'off')); 
?>
<?php
 echo render_input('lm_leadid','',$id,'hidden'); 
 if(isset($is_client) && $is_client){
  echo render_input('is_client','','client','hidden'); 
 }else{
   echo render_input('is_client','','lead','hidden'); 
 }
 ?>
<div class="modal-body">
  <div class="row">
    <div class="col-md-12">
      <?= render_textarea('message',_l('lead_manager_message_data'),'',['required'=>'required']); ?>
    </div>
  </div>
</div>
<?php echo form_close(); ?>
<div class="modal-footer">
  <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
  <button type="button" onclick="validate_sms_form('<?= $id;?>');" class="btn btn-info send_sms_btn_lm" data-lead = "<?= $id;?>" data-loading-text="<?php echo _l('wait_text'); ?>" autocomplete="off" data-form="#sms-form"><?php echo _l('send'); ?></button>
</div>
