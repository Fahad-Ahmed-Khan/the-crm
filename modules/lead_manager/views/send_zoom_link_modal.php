<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style type="text/css">
  .bootstrap-select>select.bs-select-hidden, select.bs-select-hidden, select.selectpicker {
    display: block!important;
  }
  .form-control[disabled], .form-control[readonly], fieldset[disabled] .form-control {
    background-color: #eef1f6;
    border-color: #d1dbe5;
    color: #9b9ea2;
    cursor: not-allowed;
  }
</style>
<div class="modal-header">
 <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
 <h4 class="modal-title">
  Schedule Meeting 
</h4> 
</div>
<?php echo form_open(admin_url('lead_manager/zoom_meeting/createZoomMeeting/'.$lead->id),array('id'=>'zoom_meeting_form','autocomplete'=>'off')); ?>

<div class="modal-body">
  <div class="row">
   <div class="col-md-12"> 
     <div class="row">
      <div class="col-md-6">
        <?php 
        $value = (isset($lead) && isset($lead->name) ? $lead->name : $lead->firstname.' '.$lead->lastname); 
         echo render_input('user_name',_l('lm_customer_name'),$value,'text',array('readonly'=>true)); ?>
      </div>
      <div class="col-md-6">
        <?php $value = (isset($lead) ? $lead->email : ''); 
         echo render_input('user_email',_l('lm_customer_email'),$value,'email',array('readonly'=>true)); ?>
      </div>

    </div>
  </div>
  <div class="col-md-12">
    <div class="row">
      <div class="col-md-6">
        <?php
        if(!$is_client){
          $value = (isset($lead) ? get_staff_full_name($lead->assigned) : '');
          echo render_input('staff_name',_l('lm_staff_name'),$value,'text',array('readonly'=>true)); 
        }else{
          echo render_select('staff_name',$staffs,['staffid','full_name'],_l('lm_staff_name'));
        }
        ?>
      </div>
      <div class="col-md-6">
       <div class="form-group">
        <label for="zoom_timezone" class="control-label"><?php echo _l('Country'); ?></label>
        <select name="meeting_country" id="meeting_country" class="form-control selectpicker" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" data-live-search="true">
          <?php foreach(get_all_countries() as $key => $countres){ ?>
            <option value="<?php echo $countres['country_id']; ?>" <?php if(isset($lead->country) && $lead->country == $countres['country_id']){echo 'selected';} ?>><?php echo $countres['short_name']; ?></option>
          <?php } ?>
        </select>
      </div>

    </div>
    <?php
    if(!$is_client){
      $staff_data=get_staff($lead->assigned);
      echo render_input('staff_email','',$staff_data->email,'hidden');
      echo render_input('staff_id','',$lead->assigned,'hidden');
    }else{
      echo render_input('is_client','',$is_client,'hidden'); 
    }
    echo render_input('lead_id','',$lead->id,'hidden'); 
    ?>
  </div>
</div>
<div class="col-md-12">
  <div class="row">
    <div class="col-md-6">
     <div class="form-group">
      <label for="zoom_timezone" class="control-label"><?php echo _l('settings_localization_default_timezone'); ?></label>
      <select name="zoom_timezone" id="zoom_timezone" class="form-control selectpicker" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" data-live-search="true">
        <?php foreach(get_timezones_list() as $key => $timezones){ ?>
          <optgroup label="<?php echo $key; ?>">
            <?php foreach($timezones as $timezone){ ?>
              <option value="<?php echo $timezone; ?>" <?php if(get_option('default_timezone') == $timezone){echo 'selected';} ?>><?php echo $timezone; ?></option>
            <?php } ?>
          </optgroup>
        <?php } ?>
      </select>
    </div>
  </div>
  <div class="col-md-6">
   <?php echo render_datetime_input('meeting_start_date','Date',date('Y/m/d H:i'),array('data-date-min-date'=>_d(date('Y/m/d H:i')))); ?>
 </div>
</div>
</div>

<div class="col-md-12">
  <div class="row">
    <div class="col-md-12">
      <?php echo render_input('meeting_duration',_l('zoom_meeting_duration'),'','number',array('required'=>true,'min'=>0)); ?>
    </div>
  </div>
</div>
<div class="col-sm-12">
 <div class="row">
  <div class="col-md-12">
    <?php echo render_input('meeting_agenda',_l('zoom_meeting_agenda'),''); ?>
  </div>
</div>
</div>
<div class="col-md-12">
  <?= render_textarea('meeting_description',_l('lead_manager_zoom_description '),'',['required'=>'required']); ?>
</div>
<div class="col-md-12">
  <label><strong>Meeting Options</strong></label>
  <div class="row">

   <div class="col-md-6">
    <label class="form-check-label">
      <input type="checkbox" name="meeting_option[]" class="form-check-input" value="allow_participants_to_join_anytime"> &nbsp;<?=_l('allow_participants_to_join_anytime');  ?>
    </label>
  </div>
  <div class="col-md-6">
   <label class="form-check-label">
    <input type="checkbox" name="meeting_option[]" class="form-check-input" value="mute_participants_upon_entry"> &nbsp;<?=_l('mute_participants_upon_entry');  ?>
  </label>
</div>
<div class="col-md-6">
  <label class="form-check-label">
    <input type="checkbox" name="meeting_option[]" class="form-check-input" value="automatically_record_meeting_on_the_local_computer"> &nbsp;<?=_l('automatically_record_meeting_on_the_local_computer');  ?>
  </label>
</div>
</div>
</div>
</div>
</div>
<div class="modal-footer">
  <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
  <button type="submit"  class="btn btn-info send_sms_btn_lm" data-lead = "<?= $lead->id;?>" data-loading-text="<?php echo _l('wait_text'); ?>" autocomplete="off" data-form="#sms-form"><?php echo _l('send'); ?></button>
</div>
<?php echo form_close(); ?>
<script type="text/javascript">
  $(function() {
    jQuery('#meeting_start_date').datetimepicker();
    jQuery('#staff_name').selectpicker();
  });
</script>
