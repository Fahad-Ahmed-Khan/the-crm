<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog modal-md">
	<div class="modal-content data">
		<div class="modal-header customer-profile-group-heading" style="margin: 0;">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true" style="color:black;">&times;</span></button>
			<h4 class="modal-title" id="gridSystemModalLabel"><?php echo _l('lm_mailbox_compose_label'); ?></h4>
		</div>
		<div class="modal-body">
			<form id="mailbox-compose-form" enctype="multipart/form-data">
			<?php 
				$csrf = get_csrf_for_ajax();
				echo form_hidden('csrf_token_name',$csrf['hash']); 
			?>
				<div class="row">
					<div class="col-sm-12">
						<?php
					// if(is_null($lead->email)){
					// 	echo render_input('to',_l('lm_mailbox_to_label'), '', 'email'); 
					// }else{
					// 	$value = isset($lead->email) && $lead->email ? $lead->email : ''; 
					// 	$disabled = isset($lead->email) && $lead->email ? ['disabled' => 'disabled'] : [];
					// 	echo render_input('',_l('lm_mailbox_to_label'), $value, 'email', $disabled); 
					// 	echo form_hidden('to',$value);
					// }
					if(isset($lead->email) && !empty($lead->email)){
						echo render_input('to',_l('lm_mailbox_to_label'), $lead->email, 'email', ['readonly' => 'readonly']); 
					}else{
						echo render_input('to',_l('lm_mailbox_to_label'), '', 'email'); 
					}
					?>
					</div>
					<div class="col-sm-12">
						<?php echo render_input('to_cc',_l('lm_mailbox_cc_label')); ?>
					</div>
					<div class="col-sm-12">
						<?php echo render_input('subject',_l('lm_mailbox_subject_label')); ?>
					</div>
					<div class="col-sm-12">
						<?php echo render_textarea('message','','',array(),array(),'','tinymce'); ?>
					</div>
					<div class="col-sm-12">
						<div class="row attachments">
							<div class="attachment">
								<div class="col-md-12">
									<div class="form-group">
										<label for="attachment" class="control-label"><?php echo _l('add_task_attachments'); ?></label>
										<div class="input-group">
											<input type="file" extension="<?php echo str_replace('.','',get_option('allowed_files')); ?>" filesize="<?php echo file_upload_max_size(); ?>" class="form-control" name="attachments[0]">
											<span class="input-group-btn">
												<button class="btn btn-success add_more_attachments p8" type="button"><i class="fa fa-plus"></i></button>
											</span>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
			<button id="lm-send-email-btn" class="btn btn-info"><?php echo _l('send'); ?></button>
		</div>
	</div>
</div>
