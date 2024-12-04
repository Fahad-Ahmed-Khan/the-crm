<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style type="text/css">
	.mail_rply_cl{}
	.mail_rply_cl a{    padding: 7px 10px;
		border: 1px solid #d9d8d8;
		border-radius: 4px;}
		.mail_rply_cl a i{}
	</style>

	<div class="modal-dialog modal-md">
		<div class="modal-content data">
			<div class="modal-header customer-profile-group-heading" style="margin: 0;">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true" style="color:black;">&times;</span></button>
				<h4 class="modal-title"><?php echo _l('lm_mailbox_email_label').'#'._d($email->created_date); ?></h4>
			</div>
			<div class="modal-body">
				<div class="row">
					<div class="col-md-12">
						<div class="mail_rply_cl mbot5 text-right">
							<?php if($email->status != 'draft'){ ?>
								<a href="javascript:void(0);" id="reply-mailbox-link"><i class="fa fa-reply" aria-hidden="true"></i> <?php echo _l('lm_mailbox_email_reply'); ?></a>
							<?php } ?>
							<a href="javascript:void(0);" id="forword-mailbox-link"><i class="fa fa-share" aria-hidden="true"></i> <?php echo _l('lm_mailbox_email_forword'); ?></a>
						</div>
					</div>
					<div class="col-md-4 col-xs-12">
						<p class="text-muted no-mtop"><?php echo _l('lm_mailbox_email_from_name'); ?></p>
					</div>
					<div class="col-md-6 col-xs-12">
						<p class="bold"><?php echo $email->fromName; ?></p>
					</div>
					<div class="col-md-4 col-xs-12">
						<p class="text-muted no-mtop"><?php echo _l('lm_mailbox_email_from_email'); ?></p>
					</div>
					<div class="col-md-6 col-xs-12">
						<p class="bold"><?php echo $email->from_email; ?></p>
					</div>
					<div class="col-md-4 col-xs-12">
						<p class="text-muted no-mtop"><?php echo _l('lm_mailbox_email_subject'); ?></p>
					</div>
					<div class="col-md-6 col-xs-12">
						<p class="bold"><?php echo $email->subject; ?></p>
					</div>
					<div class="col-md-4 col-xs-12">
						<p class="text-muted no-mtop"><?php echo _l('lm_mailbox_email_message'); ?></p>
					</div>
					<div class="col-md-6 col-xs-12">
						<p class="bold read-more"><?php echo $email->message; ?></p>
					</div>
				</div>
				
				<div class="row hide" id="reply-div">
					<form id="mailbox-reply-form" action="<?php echo admin_url('lead_manager/mailbox_mail_reply'); ?>">
						<?php 
						echo form_hidden('to', $email->from_email);
						echo form_hidden('subject', $email->subject); 
						$csrf = get_csrf_for_ajax();
						echo form_hidden('csrf_token_name',$csrf['hash']); 
						?>
						<div class="col-md-12 col-xs-12">
							<?php echo render_input('to_cc',_l('lm_mailbox_cc_label')); ?>
							<?php echo render_textarea('message','','',array(),array(),'','message'); ?>
							<div class="attachments mbot10">
								<div class="attachment">
									<div class="form-group">
										<div class="input-group">
											<input type="file" extension="<?php echo str_replace('.','',get_option('allowed_files')); ?>" filesize="<?php echo file_upload_max_size(); ?>" class="form-control" name="attachments[0]">
											<span class="input-group-btn mtop5">
												<button class="btn btn-success add_more_attachments p8" type="button"><i class="fa fa-plus"></i></button>
											</span>
										</div>
									</div>
								</div>
							</div>
							<div class="mail_rply_cl mbot5 text-right">
								<button id="reply-mailbox-btn" class="btn mright5 btn-info pull-right display-block"><?php echo _l('send'); ?> <i class="fa fa-paper-plane-o" aria-hidden="true"></i></button>
							</div>
						</div>
					</form>
				</div>
				<div class="row hide" id="forword-div">
					<form id="mailbox-forword-form" action="<?php echo admin_url('lead_manager/mailbox_mail_reply'); ?>">
						<?php 
						//echo form_hidden('to', $email->from_email);
						echo form_hidden('subject', $email->subject); 
						echo form_hidden('message', $email->message); 
						$csrf = get_csrf_for_ajax();
						echo form_hidden('csrf_token_name',$csrf['hash']); 
						?>
						<div class="col-md-12 col-xs-12">
							<?php echo render_input('to',_l('lm_mailbox_to_label')); ?>
							<?php echo render_input('to_cc',_l('lm_mailbox_cc_label')); ?>
							<div class="mail_rply_cl mbot5 text-right">
								<button id="forword-mailbox-btn" class="btn mright5 btn-info pull-right display-block"><?php echo _l('send'); ?> <i class="fa fa-paper-plane-o" aria-hidden="true"></i></button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- <div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
		</div> -->
	</div>
</div>
