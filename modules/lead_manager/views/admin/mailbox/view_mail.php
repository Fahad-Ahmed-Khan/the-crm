<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<link rel="stylesheet" type="text/css" href="<?php echo base_url('modules/lead_manager/assets/css/mailbox_email.css'); ?>">
<div id="wrapper">
   <div class="content">
      <div class="row">
         <div class="panel_s">
            <div class="panel-body">
               <div class="row">
                  <div class="col-lg-6">
                     <div class="row wrap_mail_header_filter">
                        <div class="col-md-1">
                           <div>
                              <a class="border-right" href="javascript:void(0);" data-id="<?php echo $mail->id; ?>" data-param="<?php echo $mail->is_favourite ? 'unstar' : 'unstar'; ?>" onclick="lm_mb_view_mail_action(this); return false;">
                                 <?php echo $mail->is_favourite ? '<i class="fa fa-star text-warning" aria-hidden="true"></i>' : '<i class="fa fa-star-o text-muted" aria-hidden="true"></i>'; ?>
                              </a>
                           </div>
                        </div>
                        <div class="col-md-1">
                           <a class="border-right" href="javascript:void(0);" data-id="<?php echo $mail->id; ?>" data-param="<?php echo $mail->is_bookmark ? 'unbookmark' : 'bookmark'; ?>" onclick="lm_mb_view_mail_action(this); return false;">
                              <?php echo $mail->is_bookmark ? '<i class="fa fa-bookmark" aria-hidden="true"></i>' : '<i class="fa fa-bookmark-o text-muted" aria-hidden="true"></i>'; ?>
                           </a>
                        </div>
                        <div class="col-md-1">
                           <a class="border-right" href="javascript:void(0);" data-id="<?php echo $mail->id; ?>" data-param="delete" onclick="lm_mb_view_mail_action(this); return false;">
                              <i class="fa fa-trash text-danger" aria-hidden="true"></i>
                           </a>
                        </div>
                     </div>
                  </div>
                  <div class="col-lg-6">
                     <div class="newer_older_cl">
                        <div>
                           <a href="<?php echo isset($prev_mail_id->id) ? admin_url('lead_manager/view_email/' . $prev_mail_id->id) : admin_url('lead_manager/mailbox'); ?>" data-toggle="tooltip" data-placement="top" title="Older"><i class="fa fa-angle-left" aria-hidden="true"></i></a>
                        </div>
                        <div>
                           <a href="<?php echo isset($next_mail_id->id) ? admin_url('lead_manager/view_email/' . $next_mail_id->id) : '#'; ?>" data-toggle="tooltip" data-placement="top" title="Newer"><i class="fa fa-angle-right" aria-hidden="true"></i></a>
                        </div>
                        <div>
                           <?php
                           ?>
                           <a href="<?php echo admin_url('lead_manager/mailbox?dir=' . $mail->direction . '&st=' . $mail->status); ?>" class="btn btn-primary"><?php echo _l('lm_back_to_' . $mail->status); ?></a>
                        </div>
                     </div>
                  </div>
               </div>
               <hr class="hr-panel-heading mbot5">
               <div class="mail_header_title">
                  <div class="row">
                     <div class="col-md-12">
                        <div class="mail_tilte_right">
                           <h3><?php echo $mail->subject; ?></h3>
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="user_main_details">
                           <div>
                              <img src="<?php echo base_url('assets/images/user-placeholder.jpg'); ?>" class="img img-responsive">
                           </div>
                           <div>
                              <div class="mail_norply">
                                 <p><?php echo $mail->fromName ?? $mail->from_email; ?></p>
                                 <?php if ($mail->direction == 'incoming') { ?>
                                    <small>To : me </small>
                                 <?php } else { ?>
                                    <small>To : <?php echo $mail->to_email; ?> </small>
                                 <?php } ?>
                                 <?php if (!empty($mail->to_cc)) { ?>
                                    <br><small>CC : <?php echo $mail->to_cc; ?> </small>
                                 <?php } ?>
                              </div>
                           </div>
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="newer_older_cl">
                           <div>
                              <?php echo _dt($mail->mail_date); ?>
                              (<small title="<?php echo _dt($mail->mail_date); ?>"><?php echo time_ago($mail->mail_date); ?></small>)
                           </div>
                           <div>
                              <a href="#" id="reply-mailbox-link" data-toggle="tooltip" data-placement="top" title="Reply" class="rply_cl"><i class="fa fa-reply" aria-hidden="true"></i></a>
                           </div>
                           <div>
                              <a href="#" id="forword-mailbox-link" data-toggle="tooltip" data-placement="top" title="Forword" class="rply_cl"><i class="fa fa-share" aria-hidden="true"></i></a>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <hr class="hr-panel-heading" />



               <?php if ($mail->status == 'draft') { ?>
                  <div class="row" id="message-div">
                     <form id="mailbox-draft-form" action="<?php echo admin_url('lead_manager/mailbox_mail_reply'); ?>" enctype="multipart/form-data">
                        <?php
                        $csrf = get_csrf_for_ajax();
                        echo form_hidden('csrf_token_name', $csrf['hash']);
                        echo form_hidden('mail_id', $mail->id);
                        echo form_hidden('is_draft', 1);
                        ?>
                        <div class="col-md-6 col-xs-6">
                           <?php
                           echo render_input('to', _l('lm_mailbox_to_label'), $mail->from_email); ?>
                        </div>
                        <div class="col-md-6 col-xs-6">
                           <?php
                           echo render_input('to_cc', _l('lm_mailbox_cc_label'), $mail->to_cc);
                           ?>
                        </div>
                        <div class="col-md-12 col-xs-12">
                           <?php
                           echo render_input('subject', _l('lm_mailbox_subject_label'), $mail->subject);
                           echo render_textarea('message', '', $mail->message, array(), array(), '', 'tinymce'); ?>
                           <div class="attachments mbot10">
                              <div class="attachment">
                                 <div class="form-group">
                                    <div class="input-group">
                                       <input type="file" extension="<?php echo str_replace('.', '', get_option('allowed_files')); ?>" filesize="<?php echo file_upload_max_size(); ?>" class="form-control" name="attachments[0]">
                                       <span class="input-group-btn mtop5">
                                          <button class="btn btn-success add_more_attachments p8" type="button"><i class="fa fa-plus"></i></button>
                                       </span>
                                    </div>
                                 </div>
                              </div>
                           </div>
                           <div class="mail_rply_cl mbot5 text-right">
                              <button id="draft-mailbox-btn" class="btn mright5 btn-info pull-right display-block" data-loading-text="<?php echo _l('wait_text'); ?>" autocomplete="off"><?php echo _l('send'); ?> <i class="fa fa-paper-plane-o" aria-hidden="true"></i></button>
                           </div>
                        </div>
                     </form>
                  </div>
               <?php } else { ?>
                  <div class="row" id="message-div">
                     <div class="col-md-7">
                        <?php echo $mail->message; ?>
                     </div>
                     <div class="col-md-5">
                        <?php if ($mail->is_attachment) { ?>
                           <div class="mail-attachment_cl">
                              <div class="achmnt_head">
                                 <h3><?php echo _l('lm_mailbox_attachments'); ?></h3>
                              </div>
                              <div class="row">
                                 <?php
                                 if (isset($attachments) && !empty($attachments)) {
                                    foreach ($attachments as $attachment) {
                                 ?>
                                       <div class="col-lg-4">
                                          <div class="card text-center inner_atch_wrpa">
                                             <div class="card-body">
                                                <p class="card-text"><?php $icon = get_file_icons($attachment['filetype']);
                                                                     echo $icon; ?></p>
                                             </div>
                                             <div class="card-footer">
                                                <?php echo $attachment['file_name']; ?>
                                             </div>
                                             <?php
                                             $fileName = LEAD_MANAGER_MAILBOX_FOLDER . $attachment['mailbox_id'] . '/' . $attachment['file_name'];
                                             if (file_exists($fileName)) {
                                             ?>
                                                <div class="overlay_hover">
                                                   <a href="<?php echo base_url('uploads/lead_manager/mailbox/' . $attachment['mailbox_id'] . '/' . $attachment['file_name']); ?>" download=""><i class="fa fa-download" aria-hidden="true"></i></a>
                                                   <small><?php echo formatSizeUnits(filesize(LEAD_MANAGER_MAILBOX_FOLDER . $attachment['mailbox_id'] . '/' . $attachment['file_name'])); ?></small>
                                                </div>
                                             <?php } ?>
                                          </div>
                                       </div>
                                 <?php }
                                 } ?>
                              </div>
                           </div>
                        <?php } ?>
                     </div>
                  </div>
               <?php } ?>
               <div class="row hide" id="reply-div">
                  <form id="mailbox-reply-form" action="<?php echo admin_url('lead_manager/mailbox_mail_reply'); ?>" enctype="multipart/form-data">
                     <?php
                     echo form_hidden('to', $mail->from_email);
                     echo form_hidden('subject', $mail->subject);
                     $csrf = get_csrf_for_ajax();
                     echo form_hidden('csrf_token_name', $csrf['hash']);
                     ?>
                     <div class="col-md-12 col-xs-12">
                        <?php echo render_textarea('message', '', '', array(), array(), '', 'tinymce'); ?>
                        <div class="attachments mbot10">
                           <div class="attachment">
                              <div class="form-group">
                                 <div class="input-group">
                                    <input type="file" extension="<?php echo str_replace('.', '', get_option('allowed_files')); ?>" filesize="<?php echo file_upload_max_size(); ?>" class="form-control" name="attachments[0]">
                                    <span class="input-group-btn mtop5">
                                       <button class="btn btn-success add_more_attachments p8" type="button"><i class="fa fa-plus"></i></button>
                                    </span>
                                 </div>
                              </div>
                           </div>
                        </div>
                        <div class="mail_rply_cl mbot5 text-right">
                           <button id="reply-mailbox-btn" class="btn mright5 btn-info pull-right display-block" data-loading-text="<?php echo _l('wait_text'); ?>" autocomplete="off"><?php echo _l('send'); ?> <i class="fa fa-paper-plane-o" aria-hidden="true"></i></button>
                        </div>
                     </div>
                  </form>
               </div>
               <div class="row hide" id="forword-div">
                  <form id="mailbox-forword-form" action="<?php echo admin_url('lead_manager/mailbox_mail_reply'); ?>" enctype="multipart/form-data">
                     <?php
                     echo form_hidden('subject', $mail->subject);
                     echo form_hidden('message', $mail->message);
                     $csrf = get_csrf_for_ajax();
                     echo form_hidden('csrf_token_name', $csrf['hash']);
                     ?>
                     <div class="col-md-12 col-xs-12">
                        <?php echo render_input('to', _l('lm_mailbox_to_label')); ?>
                        <?php echo render_textarea('message', '', '', array(), array(), '', 'tinymce'); ?>
                        <div class="attachments mbot10">
                           <div class="attachment">
                              <div class="form-group">
                                 <div class="input-group">
                                    <input type="file" extension="<?php echo str_replace('.', '', get_option('allowed_files')); ?>" filesize="<?php echo file_upload_max_size(); ?>" class="form-control" name="attachments[0]">
                                    <span class="input-group-btn mtop5">
                                       <button class="btn btn-success add_more_attachments p8" type="button"><i class="fa fa-plus"></i></button>
                                    </span>
                                 </div>
                              </div>
                           </div>
                        </div>
                        <div class="mail_rply_cl mbot5 text-right">
                           <button id="forword-mailbox-btn" class="btn mright5 btn-info pull-right display-block" data-loading-text="<?php echo _l('wait_text'); ?>" autocomplete="off"><?php echo _l('send'); ?> <i class="fa fa-paper-plane-o" aria-hidden="true"></i></button>
                        </div>
                     </div>
                  </form>
               </div>
               <!-----New Design---->
               <!-- <hr class="hr-panel-heading" />
               <div class="mail_opening_list">
                  <ul class="list-unstyled">
                     <li>
                        <div class="mail_msg">
                           <div class="mail_header_title">
                              <div class="row">
                                 <div class="col-md-12">
                                    <div class="mail_tilte_right">
                                       <h3>Dernière semaine et vacaaaaaances !</h3>
                                    </div>
                                 </div>
                                 <div class="col-md-6">
                                    <div class="user_main_details">
                                       <div>
                                          <img src="https://zonvoirdemo.in/newcrm/assets/images/user-placeholder.jpg" class="img img-responsive">
                                       </div>
                                       <div>
                                          <div class="mail_norply">
                                             <p>Cook For Me</p>
                                             <small>to me</small>
                                          </div>
                                       </div>
                                    </div>
                                 </div>
                                 <div class="col-md-6">
                                    <div class="newer_older_cl">
                                       <div>
                                          09:38 (<small title="16/12/2022 9:38 AM">a month ago</small>)
                                       </div>
                                       <div>
                                          <a href="#" id="reply-mailbox-link" data-toggle="tooltip" data-placement="top" title="Reply" class="rply_cl"><i class="fa fa-reply" aria-hidden="true"></i></a>
                                       </div>
                                       <div>
                                          <a href="#" id="forword-mailbox-link" data-toggle="tooltip" data-placement="top" title="Forword" class="rply_cl"><i class="fa fa-share" aria-hidden="true"></i></a>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                           </div>
                           <div class="msg_content">
                              <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry.
                                 Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it
                                 to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting,
                                 remaining essentially unchanged. It</p>

                              <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry.
                                 Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it
                                 to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting,
                                 remaining essentially unchanged. It</p>

                              <div class="frwd_reply">
                                 <a href="#"><i class="fa fa-reply" aria-hidden="true"></i> Reply</a>
                                 <a href="#"><i class="fa fa-share" aria-hidden="true"></i> Forward</a>
                              </div>

                           </div>
                        </div>
                     </li>

                     <li>
                        <div class="child_mail">
                           <hr class="hr-panel-heading" />
                           <div class="mail_msg">
                              <div class="mail_header_title">
                                 <div class="row">
                                    <div class="col-md-12">
                                       <div class="mail_tilte_right">
                                          <h3>Dernière semaine et vacaaaaaances !</h3>
                                       </div>
                                    </div>
                                    <div class="col-md-6">
                                       <div class="user_main_details">
                                          <div>
                                             <img src="https://zonvoirdemo.in/newcrm/assets/images/user-placeholder.jpg" class="img img-responsive">
                                          </div>
                                          <div>
                                             <div class="mail_norply">
                                                <p>Cook For Me</p>
                                                <small>to me</small>
                                             </div>
                                          </div>
                                       </div>
                                    </div>
                                    <div class="col-md-6">
                                       <div class="newer_older_cl">
                                          <div>
                                             09:38 (<small title="16/12/2022 9:38 AM">a month ago</small>)
                                          </div>
                                          <div>
                                             <a href="#" id="reply-mailbox-link" data-toggle="tooltip" data-placement="top" title="Reply" class="rply_cl"><i class="fa fa-reply" aria-hidden="true"></i></a>
                                          </div>
                                          <div>
                                             <a href="#" id="forword-mailbox-link" data-toggle="tooltip" data-placement="top" title="Forword" class="rply_cl"><i class="fa fa-share" aria-hidden="true"></i></a>
                                          </div>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                              <div class="msg_content">
                                 <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry.
                                    Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it
                                    to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting,
                                    remaining essentially unchanged. It</p>

                                 <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry.
                                    Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it
                                    to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting,
                                    remaining essentially unchanged. It</p>

                                 <div class="frwd_reply">
                                    <a href="#"><i class="fa fa-reply" aria-hidden="true"></i> Reply</a>
                                    <a href="#"><i class="fa fa-share" aria-hidden="true"></i> Forward</a>
                                 </div>

                              </div>
                           </div>
                        </div>
                     </li>

                     <li>
                        <div class="sub_child_mail">
                           <hr class="hr-panel-heading" />
                           <div class="mail_msg">
                              <div class="mail_header_title">
                                 <div class="row">
                                    <div class="col-md-12">
                                       <div class="mail_tilte_right">
                                          <h3>Dernière semaine et vacaaaaaances !</h3>
                                       </div>
                                    </div>
                                    <div class="col-md-6">
                                       <div class="user_main_details">
                                          <div>
                                             <img src="https://zonvoirdemo.in/newcrm/assets/images/user-placeholder.jpg" class="img img-responsive">
                                          </div>
                                          <div>
                                             <div class="mail_norply">
                                                <p>Cook For Me</p>
                                                <small>to me</small>
                                             </div>
                                          </div>
                                       </div>
                                    </div>
                                    <div class="col-md-6">
                                       <div class="newer_older_cl">
                                          <div>
                                             09:38 (<small title="16/12/2022 9:38 AM">a month ago</small>)
                                          </div>
                                          <div>
                                             <a href="#" id="reply-mailbox-link" data-toggle="tooltip" data-placement="top" title="Reply" class="rply_cl"><i class="fa fa-reply" aria-hidden="true"></i></a>
                                          </div>
                                          <div>
                                             <a href="#" id="forword-mailbox-link" data-toggle="tooltip" data-placement="top" title="Forword" class="rply_cl"><i class="fa fa-share" aria-hidden="true"></i></a>
                                          </div>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                              <div class="msg_content">
                                 <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry.
                                    Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it
                                    to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting,
                                    remaining essentially unchanged. It</p>

                                 <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry.
                                    Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it
                                    to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting,
                                    remaining essentially unchanged. It</p>

                                 <div class="frwd_reply">
                                    <a href="#"><i class="fa fa-reply" aria-hidden="true"></i> Reply</a>
                                    <a href="#"><i class="fa fa-share" aria-hidden="true"></i> Forward</a>
                                 </div>

                              </div>
                           </div>
                        </div>
                     </li>
                  </ul>

                  <hr class="hr-panel-heading" />

                  <ul class="list-unstyled">
                     <li>
                        <div class="mail_msg">
                           <div class="mail_header_title">
                              <div class="row">
                                 <div class="col-md-12">
                                    <div class="mail_tilte_right">
                                       <h3>Dernière semaine et vacaaaaaances !</h3>
                                    </div>
                                 </div>
                                 <div class="col-md-6">
                                    <div class="user_main_details">
                                       <div>
                                          <img src="https://zonvoirdemo.in/newcrm/assets/images/user-placeholder.jpg" class="img img-responsive">
                                       </div>
                                       <div>
                                          <div class="mail_norply">
                                             <p>Cook For Me</p>
                                             <small>to me</small>
                                          </div>
                                       </div>
                                    </div>
                                 </div>
                                 <div class="col-md-6">
                                    <div class="newer_older_cl">
                                       <div>
                                          09:38 (<small title="16/12/2022 9:38 AM">a month ago</small>)
                                       </div>
                                       <div>
                                          <a href="#" id="reply-mailbox-link" data-toggle="tooltip" data-placement="top" title="Reply" class="rply_cl"><i class="fa fa-reply" aria-hidden="true"></i></a>
                                       </div>
                                       <div>
                                          <a href="#" id="forword-mailbox-link" data-toggle="tooltip" data-placement="top" title="Forword" class="rply_cl"><i class="fa fa-share" aria-hidden="true"></i></a>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                           </div>
                           <div class="msg_content">
                              <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry.
                                 Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it
                                 to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting,
                                 remaining essentially unchanged. It</p>

                              <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry.
                                 Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it
                                 to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting,
                                 remaining essentially unchanged. It</p>

                              <div class="frwd_reply">
                                 <a href="#"><i class="fa fa-reply" aria-hidden="true"></i> Reply</a>
                                 <a href="#"><i class="fa fa-share" aria-hidden="true"></i> Forward</a>
                              </div>

                           </div>
                        </div>
                     </li>

                     <li>
                        <div class="child_mail">
                           <hr class="hr-panel-heading" />
                           <div class="mail_msg">
                              <div class="mail_header_title">
                                 <div class="row">
                                    <div class="col-md-12">
                                       <div class="mail_tilte_right">
                                          <h3>Dernière semaine et vacaaaaaances !</h3>
                                       </div>
                                    </div>
                                    <div class="col-md-6">
                                       <div class="user_main_details">
                                          <div>
                                             <img src="https://zonvoirdemo.in/newcrm/assets/images/user-placeholder.jpg" class="img img-responsive">
                                          </div>
                                          <div>
                                             <div class="mail_norply">
                                                <p>Cook For Me</p>
                                                <small>to me</small>
                                             </div>
                                          </div>
                                       </div>
                                    </div>
                                    <div class="col-md-6">
                                       <div class="newer_older_cl">
                                          <div>
                                             09:38 (<small title="16/12/2022 9:38 AM">a month ago</small>)
                                          </div>
                                          <div>
                                             <a href="#" id="reply-mailbox-link" data-toggle="tooltip" data-placement="top" title="Reply" class="rply_cl"><i class="fa fa-reply" aria-hidden="true"></i></a>
                                          </div>
                                          <div>
                                             <a href="#" id="forword-mailbox-link" data-toggle="tooltip" data-placement="top" title="Forword" class="rply_cl"><i class="fa fa-share" aria-hidden="true"></i></a>
                                          </div>
                                       </div>
                                    </div>
                                 </div>
                              </div>
                              <div class="msg_content">
                                 <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry.
                                    Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it
                                    to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting,
                                    remaining essentially unchanged. It</p>

                                 <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry.
                                    Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it
                                    to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting,
                                    remaining essentially unchanged. It</p>

                                 <div class="frwd_reply">
                                    <a href="#"><i class="fa fa-reply" aria-hidden="true"></i> Reply</a>
                                    <a href="#"><i class="fa fa-share" aria-hidden="true"></i> Forward</a>
                                 </div>

                              </div>
                           </div>
                        </div>
                     </li>


                  </ul>

               </div> -->
               <!-----Close---->
            </div>
         </div>
      </div>
   </div>
   <?php init_tail(); ?>
   <script></script>
   </body>

   </html>