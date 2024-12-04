<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="contact-profile">
   <?php if ($is_client) {
      $primary_contact_id = get_primary_contact_user_id($lead->userid);
      if (isset($primary_contact_id) && !empty($primary_contact_id)) {
         $profile_image = contact_profile_image_url($primary_contact_id);
      }
      echo '<img src="' . $profile_image . '" alt="" />';
      echo '<p>' . $lead->company . '<small>' . $lead->phonenumber . '</small></p>';
   } else {
      echo '<img src="' . base_url("assets/images/user-placeholder.jpg") . '" alt="" />';
      echo '<p>' . $lead->name . '<small>' . $lead->phonenumber . '</small></p>';
   } ?>
</div>
<div class="messages <?php echo $this->uri->segment($this->uri->total_segments()) == 'load_conversation_whatsapp' && count($chats) == 0 ? 'start_chat' : ''; ?>">
<?php
   $sender_id = $is_client ? $lead->userid : $lead->id;
   $sender_type = $is_client ? 'client' : 'lead';
   if ("load_conversation_whatsapp" == $this->uri->segment($this->uri->total_segments()) && count($chats) == 0) {
      echo '<div class="startchatwrapbtn"><button data-id="'.$sender_id.'" data-type="'.$sender_type.'" onclick="newWhatsappMessageOutgoingFirst(this)">'._l("lm_whatsapp_start_chat_btn_text").' <i class="fa-brands fa-whatsapp"></i></button></div>';
   }
   ?>
   <ul id="messages-ul" data-userid="<?php echo $sender_id; ?>" data-usertype="<?php echo $sender_type; ?>">
      <?php
      if (isset($chats) && !empty($chats)) {
         foreach ($chats as $chat) {
      ?>
            <li id="<?php echo $chat['id']; ?>" class="<?php echo $chat['sms_direction']; ?>">
               <?php if ($chat['sms_direction'] == 'incoming') {
                  echo '<img src="' . base_url('assets/images/user-placeholder.jpg') . '" alt="" data-toggle="tooltip" data-placement="top" title="'.$lead->name.'" />';
               } else {
                  if (isset($staff->profile_image) && !empty($staff->profile_image)) {
                     echo '<img src="' . $staff->profile_image . '" alt="" data-toggle="tooltip" data-placement="top" title="'.$staff->full_name.'"/>';
                  } else {
                     echo '<img src="' . base_url('assets/images/user-placeholder.jpg') . '" alt="" data-toggle="tooltip" data-placement="top" title="'.$staff->full_name.'"/>';
                  }
               } ?>
               <p>
                  <?php
                  if ("load_conversation_whatsapp" == $this->uri->segment($this->uri->total_segments())) {
                     if ($chat['is_files'] && isset($chat['file_name'])) {
                        if (substr($chat['filetype'], 0, 5) == 'image') {
                           echo "<a href='" . base_url('uploads/lead_manager/whatsapp/' . $chat['type'] . '/' . $chat['from_id'] . '/' . $chat['to_id'] . '/' . $chat['file_name']) . "' data-lightbox=='image-". base_url('uploads/lead_manager/whatsapp/' . $chat['type'] . '/' . $chat['from_id'] . '/' . $chat['to_id'] . '/' . $chat['file_name']) ."'><img class='whatsapp_img_thumb' src='" . base_url('uploads/lead_manager/whatsapp/' . $chat['type'] . '/' . $chat['from_id'] . '/' . $chat['to_id'] . '/' . $chat['file_name']) . "'></a>";
                        } else {
                           echo "<i class='fa fa-file-text-o whatsapp_file_thumb'></i>";
                        }
                     } else {
                        echo $chat['sms_body'];
                     }
                  } else {
                     echo $chat['sms_body'];
                  }
                  ?>
               </p>
               <small><?php echo isset($chat['sms_date']) && !empty($chat['sms_date']) ?  _dt($chat['sms_date']) : _dt($chat['added_at']) ; ?></small>
               <span class="sms_status"><?php echo $chat['sms_status']; ?></span>
            </li>
      <?php }
      } ?>
   </ul>
</div>
<div class="message-input">
   <div class="wrap">
      <?php
      $sender_id = $is_client ? $lead->userid : $lead->id;
      $sender_type = $is_client ? 'client' : 'lead';
      if ("load_conversation_whatsapp" == $this->uri->segment($this->uri->total_segments())) { ?>
         <div class="whatsapp_dropzone_area">
            <?php
            echo form_open_multipart(admin_url('lead_manager/whatsapp_upload_file/' . $sender_id . '/' . $sender_type), array('class' => 'dropzone', 'id' => 'whatsapp-files-upload', 'style' => 'display:none;'));
            ?>
            <input type="file" name="file" multiple />
            <?php echo form_close(); ?>
            <div class="dropzone-previews"></div>
         </div>
      <?php } ?>
      <input type="text" placeholder="<?php echo _l('lm_chat_input_placeholder'); ?>" />
      <div class="two_btn_wrap">
         <?php if ("load_conversation_whatsapp" == $this->uri->segment($this->uri->total_segments())) { ?>
            <button class="attachment_btn" data-drop="begin"> <i class="fa fa-paperclip attachment" aria-hidden="true"></i></button>
         <?php } ?>
         <?php
         echo '<button class="submit" data-lead="' . $sender_id . '" data-type="' . $sender_type . '"><i class="fa fa-paper-plane" data-lead="' . $sender_id . '" data-type="' . $sender_type . '" aria-hidden="true"></i></button>';
         ?>
      </div>
   </div>
</div>