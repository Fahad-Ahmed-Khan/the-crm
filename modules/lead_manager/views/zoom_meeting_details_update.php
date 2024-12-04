<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title">
        Update Meeting Details
    </h4>
</div>
<style type="">
    .cu_zoom td{    width: 20%;     font-size: 14px;     padding-bottom: 10px !important;}
  .cu_zoom th{       font-weight: 500;
    font-size: 13px;
    width: 15%;}

.cu_zoom {
    max-width: 90%;
    margin: 0 auto;
    margin-top: 0 !important;
}

.cu_zoom {
    max-width: 86%;
    margin: 0 auto;
    margin-top: 0 !important;
}
</style>
<div class="modal-body">
    <div class="row">
        <div class="col-md-12">
            <?php
            $lead = $meeting_details;
            //  print_r($lead);
            echo form_open(admin_url('lead_manager/zoom_meeting/updateZoomMeeting/' . $lead->meeting_id)); ?>

            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <?php
                                $value = (isset($lead) && isset($lead->name) ? $lead->name : $lead->firstname . ' ' . $lead->lastname);
                                echo render_input('user_name', _l('lm_customer_name'), $value, 'text', array('readonly' => true)); ?>
                            </div>
                            <div class="col-md-6">
                                <?php $value = (isset($lead) ? $lead->email : '');
                                echo render_input('user_email', _l('lm_customer_email'), $value, 'email', array('readonly' => true)); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <?php
                                $value = (isset($meeting_details)) ? $meeting_details->staff_name : 'NA';
                                echo render_input('staff_name', _l('lm_staff_name'), $value, 'text', array('readonly' => true)); ?>
                            </div>
                            <div class="col-md-6">
                                <?php $value = (isset($meeting_details)) ? $meeting_details->staff_email : 'NA';
                                echo render_input('user_email', _l('Staff Email'), $value, 'email', array('readonly' => true)); ?>
                            </div>
                        </div>
                    </div>



                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="zoom_timezone" class="control-label"><?php echo _l('settings_localization_default_timezone'); ?></label>
                                    
                                    <select name="zoom_timezone" id="zoom_timezone" class="form-control selectpicker" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" data-live-search="true">
                                        <?php  foreach (get_timezones_list() as $key => $timezones) { ?>
                                            <optgroup label="<?php echo $key; ?>">
                                                <?php foreach ($timezones as $timezone) { ?>
                                                    <option value="<?php echo $timezone; ?>" <?php if (get_option('default_timezone') == $timezone) {
                                                                                                    echo 'selected';
                                                                                                } ?>><?php echo $timezone; ?></option>
                                                <?php } ?>
                                            </optgroup>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <?php echo render_datetime_input('meeting_start_date', 'Date', date('Y/m/d H:i'), array('data-date-min-date' => _d(date('Y/m/d H:i')))); ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-12">
                                <?php $value = (isset($meeting_details)) ? $meeting_details->meeting_duration : 'NA';  
                                echo render_input('meeting_duration', _l('zoom_meeting_duration'), $value, 'number', array('required' => true, 'min' => 0)); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="row">
                            <div class="col-md-12">
                                <?php $value = (isset($meeting_details)) ? $meeting_details->meeting_agenda : 'NA'; 
                                echo render_input('meeting_agenda', _l('zoom_meeting_agenda'),$value ); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <?php $value = (isset($meeting_details)) ? $meeting_details->meeting_description : 'NA'; 
                        echo render_textarea('meeting_description', _l('lead_manager_zoom_description '), $value, ['required' => 'required']); ?>
                    </div>
                    <div class="col-md-12">
                        <label><strong>Meeting Options</strong></label>
                        <div class="row">

                            <div class="col-md-6">
                                <label class="form-check-label">
                                    <input type="checkbox" name="meeting_option[]" class="form-check-input" value="allow_participants_to_join_anytime"> &nbsp;<?= _l('allow_participants_to_join_anytime');  ?>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="form-check-label">
                                    <input type="checkbox" name="meeting_option[]" class="form-check-input" value="mute_participants_upon_entry"> &nbsp;<?= _l('mute_participants_upon_entry');  ?>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="form-check-label">
                                    <input type="checkbox" name="meeting_option[]" class="form-check-input" value="automatically_record_meeting_on_the_local_computer"> &nbsp;<?= _l('automatically_record_meeting_on_the_local_computer');  ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
    <button type="submit" class="btn btn-info send_sms_btn_lm" data-lead="<?= $lead->meeting_id; ?>" data-loading-text="<?php echo _l('wait_text'); ?>" autocomplete="off" data-form="#sms-form"><?php echo _l('send'); ?></button>
</div>
<?php echo form_close(); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function() {
        jQuery('#meeting_start_date').datetimepicker();
        jQuery('#staff_name').selectpicker();
    });
</script>