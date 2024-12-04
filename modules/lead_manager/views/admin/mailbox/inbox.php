<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<h4 class="customer-profile-group-heading"><?php echo isset($table_page) ? _l($table_page) : _l('lm_mailbox_inbox_title'); ?></h4>
<div class="row wrap_mail_header_filter">
	<div class="col-md-1">
		<a class="border-right" href="#" onclick="lm_mb_bulk_inbox('star','.table-lm-mailbox'); return false;">
			<i class="fa fa-star text-warning" aria-hidden="true"></i>
		</a>
	</div>
	<div class="col-md-1">
		<a class="border-right" href="#" onclick="lm_mb_bulk_inbox('unstar','.table-lm-mailbox'); return false;">
			<i class="fa fa-star-o" aria-hidden="true"></i>
		</a>
	</div>
	<div class="col-md-1">
		<a class="border-right" href="#" onclick="lm_mb_bulk_inbox('bookmark','.table-lm-mailbox'); return false;">
			<i class="fa fa-bookmark text-muted" aria-hidden="true"></i>
		</a>
	</div>
	<div class="col-md-1">
		<a class="border-right" href="#" onclick="lm_mb_bulk_inbox('unbookmark','.table-lm-mailbox'); return false;">
			<i class="fa fa-bookmark-o" aria-hidden="true"></i>
		</a>
	</div>
	<div class="col-md-1">
		<a class="border-right" href="#" onclick="lm_mb_bulk_inbox('delete','.table-lm-mailbox'); return false;">
			<i class="fa fa-trash text-danger" aria-hidden="true"></i>
		</a>
	</div>
	
</div>
<div class="clearfix"></div>
<hr class="hr-panel-heading mtop0">
<?php render_datatable([
	'<span class="hide"> - </span><div class="checkbox mass_select_all_wrap"><input type="checkbox" id="mass_select_all" data-to-table="lm-mailbox"><label></label></div>',
	_l('lm_mailbox_from'),
	_l('lm_mailbox_to'),
	_l('lm_mailbox_subject'),
	_l('lm_mailbox_date'),
	_l('lm_mailbox_is_read')
], 'lm-mailbox');
?>
<!-- <style type="text/css">
	.wrap_mail_header_filter {}

	.wrap_mail_header_filter a {
		display: inline-block;
		width: 100%;
		text-align: center;
	}

	.wrap_mail_header_filter .col-md-1 {
		padding: 0;
	}

	.checkbox.main_icon_check span {
		display: inline-block;
		margin: 6px;
	}

	.checkbox.main_icon_check {
		display: flex;
		align-items: center !important;
	}
</style> -->