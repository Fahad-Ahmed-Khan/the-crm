<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="alert alert-info">Only 1 active voice CALL gateway is allowed!</div>
<div class="panel-group" id="call_gateways_options" role="tablist" aria-multiselectable="false">
	<div class="panel panel-default">
		<div class="panel-heading" role="tab" id="headingtwilio">
			<h4 class="panel-title">
				<a role="button" data-toggle="collapse" data-parent="#call_gateways_options" href="#call_twilio" aria-expanded="false" aria-controls="call_twilio" class="collapsed">
					Twilio Voice Call <span class="pull-right"><i class="fa fa-sort-down"></i></span>
				</a>
			</h4>
		</div>
		<div id="call_twilio" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingtwilio" aria-expanded="false" style="height: 0px;">
			<div class="panel-body no-br-tlr no-border-color">
				<p>Twilio voice call integration is two way communication channel, means that your customers would be able to reply to the CALL. Phone numbers must be in format <a href="https://www.twilio.com/docs/glossary/what-e164" target="_blank">E.164</a>. Click <a href="https://support.twilio.com/hc/en-us/articles/223183008-Formatting-International-Phone-Numbers" target="_blank">here</a> to read more how phone numbers should be formatted. Click <a href="https://zonvoir.com/lead_manager/Api's-document" target="_blank">here</a> for module api's integration documentation.</p>
				<hr class="hr-10">
				<div class="row">
					<div class="col-md-6">
						<div class="form-group" app-field-wrapper="settings[call_twilio_account_sid]"><label for="settings[call_twilio_account_sid]" class="control-label">Account SID</label><input type="text" id="settings[call_twilio_account_sid]" name="settings[call_twilio_account_sid]" class="form-control" value="<?= get_option('call_twilio_account_sid') ?>">
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group" app-field-wrapper="settings[call_twilio_auth_token]"><label for="settings[call_twilio_auth_token]" class="control-label">Auth Token</label><input type="text" id="settings[call_twilio_auth_token]" name="settings[call_twilio_auth_token]" class="form-control" value="<?= get_option('call_twilio_auth_token'); ?>">
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<div class="form-group" app-field-wrapper="settings[call_twilio_phone_number]"><label for="settings[call_twilio_phone_number]" class="control-label">Twilio Phone Number</label><input type="text" id="settings[call_twilio_phone_number]" name="settings[call_twilio_phone_number]" class="form-control" value="<?= get_option('call_twilio_phone_number'); ?>">
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group" app-field-wrapper="settings[call_twiml_app_sid]"><label for="settings[call_twiml_app_sid]" class="control-label">Twiml App SID (<a href="https://support.twilio.com/hc/en-us/articles/223183008-Formatting-International-Phone-Numbers" target="_blank">Download</a> app configuration steps.)</label><input type="text" id="settings[call_twiml_app_sid]" name="settings[call_twiml_app_sid]" class="form-control" value="<?= get_option('call_twiml_app_sid'); ?>">
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<label for="call_twilio_recording_active" class="control-label clearfix">
								Recording Allow ?</label>
							<div class="radio radio-primary radio-inline">
								<input type="radio" id="y_opt_1_Active_record" name="settings[call_twilio_recording_active]" value="1" <?= get_option('call_twilio_recording_active') == '1' ? 'checked="checked"' : ''; ?>>
								<label for="y_opt_1_Active_record">
									YES</label>
							</div>
							<div class="radio radio-primary radio-inline">
								<input type="radio" id="y_opt_2_Active_record" name="settings[call_twilio_recording_active]" value="0" <?= get_option('call_twilio_recording_active') == '0' ? 'checked="checked"' : ''; ?>>
								<label for="y_opt_2_Active_record">
									NO</label>
							</div>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label for="call_twilio_active" class="control-label clearfix">
								Active</label>
							<div class="radio radio-primary radio-inline">
								<input type="radio" id="y_opt_1_Active" name="settings[call_twilio_active]" value="1" <?= get_option('call_twilio_active') == '1' ? 'checked="checked"' : ''; ?>>
								<label for="y_opt_1_Active">
									Yes</label>
							</div>
							<div class="radio radio-primary radio-inline">
								<input type="radio" id="y_opt_2_Active" name="settings[call_twilio_active]" value="0" <?= get_option('call_twilio_active') == '0' ? 'checked="checked"' : ''; ?>>
								<label for="y_opt_2_Active">
									No</label>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading" role="tab" id="headingzoom">
			<h4 class="panel-title">
				<a role="button" data-toggle="collapse" data-parent="#call_gateways_options" href="#zoom_meeting" aria-expanded="false" aria-controls="zoom_meeting" class="collapsed">
					Zoom Meeting <span class="pull-right"><i class="fa fa-sort-down"></i></span>
				</a>
			</h4>
		</div>
		<div id="zoom_meeting" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingzoom" aria-expanded="false" style="height: 0px;">
			<div class="panel-body no-br-tlr no-border-color">
				<p>Zoom API integration Details </p>
				<hr class="hr-10">
				<div class="row">
					<div class="col-md-6">
						<div class="form-group" app-field-wrapper="settings[zoom_api_key]"><label for="settings[zoom_api_key]" class="control-label">CLIENT ID</label><input type="text" id="settings[zoom_api_key]" name="settings[zoom_api_key]" class="form-control" value="<?= get_option('zoom_api_key') ?>">
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group" app-field-wrapper="settings[zoom_secret_key]"><label for="settings[zoom_secret_key]" class="control-label">CLIENT SECRET</label><input type="text" id="settings[zoom_secret_key]" name="settings[zoom_secret_key]" class="form-control" value="<?= get_option('zoom_secret_key'); ?>">
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group" app-field-wrapper="settings[lm_zoom_account_id]"><label for="settings[lm_zoom_account_id]" class="control-label">ZOOM ACCOUNT ID</label><input type="text" id="settings[lm_zoom_account_id]" name="settings[lm_zoom_account_id]" class="form-control" value="<?= get_option('lm_zoom_account_id'); ?>">
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<label for="call_zoom_active" class="control-label clearfix">
								Active</label>
							<div class="radio radio-primary radio-inline">
								<input type="radio" id="y_zoom_1_Active" name="settings[call_zoom_active]" value="1" <?= get_option('call_zoom_active') == '1' ? 'checked="checked"' : ''; ?>>
								<label for="y_zoom_1_Active">
									Yes</label>
							</div>
							<div class="radio radio-primary radio-inline">
								<input type="radio" id="y_zoom_2_Active" name="settings[call_zoom_active]" value="0" <?= get_option('call_zoom_active') == '0' ? 'checked="checked"' : ''; ?>>
								<label for="y_zoom_2_Active">
									No</label>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>