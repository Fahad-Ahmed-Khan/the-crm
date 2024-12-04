<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<h4 class=""><?php echo _l('lm_mb_smtp_config_heading'); ?></h4>
<form id="mailbox-config-form">
	<?php
	$csrf = get_csrf_for_ajax();
	echo form_hidden('csrf_token_name', $csrf['hash']);
	?>
	<div class="row">
		<div class="col-md-6">
			<?php
			$value = isset($setting->smtp_server) ? $setting->smtp_server : '';
			echo render_input('smtp_server', _l('lm_mb_smtp_server_field'), $value);
			?>
		</div>
		<div class="col-md-6">
			<?php
			$options = [['id' => 'tls', 'name' => 'TLS'], ['id' => 'ssl', 'name' => 'SSL'], ['id' => '', 'name' => 'No Encryption']];
			$value = isset($setting->smtp_encryption) ? $setting->smtp_encryption : '';
			echo render_select('smtp_encryption', $options, ['id', 'name'], _l('lm_mb_smtp_enc_field'), $value);
			?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<?php
			$value = isset($setting->smtp_user) ? $setting->smtp_user : '';
			echo render_input('smtp_user', _l('lm_mb_smtp_user_field'), $value);
			?>
		</div>
		<div class="col-md-6">
			<?php
			echo render_input('smtp_password', _l('lm_mb_smtp_password_field'), '', 'password');
			?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<?php
			$value = isset($setting->smtp_port) ? $setting->smtp_port : '';
			echo render_input('smtp_port', _l('lm_mb_smtp_port_field'), $value, 'number');
			?>
		</div>
		<div class="col-md-6">
			<?php
			$value = isset($setting->smtp_fromname) ? $setting->smtp_fromname : '';
			echo render_input('smtp_fromname', _l('lm_mb_smtp_fromname'), $value);
			?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<?php
			$replace_1 = isset($setting->is_smtp) && $setting->is_smtp ? 'is_smtp' : '';
			$replace_0 = isset($setting->is_smtp) && !$setting->is_smtp ? 'is_smtp' : '';
			echo render_yes_no_option_lm('is_smtp', _l('lm_mb_is_smtp_field'), _l('lm_mb_is_smtp_field_title'), '', '', $replace_1, $replace_0);
			?>

		</div>
	</div>
	<div class="clearfix"></div>
	<hr class="hr-panel-heading">
	</hr>
	<h4 class=""><?php echo _l('lm_mb_imap_config_heading'); ?></h4>
	<div class="row">
		<div class="col-md-6">
			<?php
			$value = isset($setting->imap_server) ? $setting->imap_server : '';
			echo render_input('imap_server', _l('lm_mb_imap_server_field'), $value);
			?>
		</div>
		<div class="col-md-6">
			<?php
			$options = [['id' => 'tls', 'name' => 'TLS'], ['id' => 'ssl', 'name' => 'SSL'], ['id' => '', 'name' => 'No Encryption']];
			$value = isset($setting->imap_encryption) ? $setting->imap_encryption : '';
			echo render_select('imap_encryption', $options, ['id', 'name'], _l('lm_mb_imap_enc_field'), $value);
			?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<?php
			$value = isset($setting->imap_user) ? $setting->imap_user : '';
			echo render_input('imap_user', _l('lm_mb_imap_user_field'), $value);
			?>
		</div>
		<div class="col-md-6">
			<?php
			echo render_input('imap_password', _l('lm_mb_imap_password_field'), '', 'password');
			?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<?php
			$replace_1 = isset($setting->is_imap) && $setting->is_imap ? 'is_imap' : '';
			$replace_0 = isset($setting->is_imap) && !$setting->is_imap ? 'is_imap' : '';
			echo render_yes_no_option_lm('is_imap', _l('lm_mb_is_imap_field'), _l('lm_mb_is_imap_field_title'), '', '', $replace_1, $replace_0);
			?>
		</div>
		<div class="col-md-6">
			<?php
			$value = isset($setting->imap_port) ? $setting->imap_port : '993';
			echo render_input('imap_port', _l('lm_mb_imap_port_field'), $value, 'number');
			?>
		</div>
	</div>
</form>
<div class="text-right">
	<button class="btn btn-primary" onclick="submitMailboxConfig(this);">Save</button>
</div>