<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head();  ?>
<style>
  .select_msg_optn select {
    background-color: #32465a;
    color: white;
    border-color: #32465a !important;
    border-radius: 0 !important;
    padding: 7px;
    height: auto;
  }

  .p_rght_0 {
    padding-right: 0;
  }

  .p_lft_0 {
    padding-left: 0;
  }
</style>
<div id="wrapper">
  <div class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="panel_s mtop5">
          <div id="frame" class="frame_conversation">
            <div id="sidepanel">
              <div id="profile">
                <div class="wrap">
                  <img id="profile-img" src="<?php echo staff_profile_image_url($staff->staffid); ?>" class="online" alt="" />
                  <p><?php echo $staff->full_name; ?></p>
                </div>
              </div>
              <div class="row">
                <div class="col-md-8 p_rght_0">
                  <div id="search">
                    <label for=""><i class="fa fa-search" aria-hidden="true"></i></label>
                    <input id="serch-input" type="text" ctype="lead" placeholder="<?php echo _l('lead_manager_conversation_serch_cont'); ?>" onkeyup="serachContacts(this, true);" />
                  </div>
                </div>
                <div class="col-md-4 p_lft_0">
                  <div class="select_msg_optn">
                    <select class="form-control" id="filter-select" onchange="serachContactsFilter(event);" ctype="lead">
                      <option value='all'>All</option>
                      <option value='yes'>Read</option>
                      <option value='no'>Unread</option>
                    </select>
                  </div>
                </div>
              </div>
              <div id="bottom-bar">
                <button id="btn-leads" class="active_btn" onclick="openContactTab('lead-contacts', true);"><i class="fa fa-users fa-fw" aria-hidden="true"></i> <span><?php echo _l('lead_manager_lead'); ?></span></button>
                <button id="btn-clients" onclick="openContactTab('client-contacts', true);"><i class="fa fa-users fa-fw" aria-hidden="true"></i> <span><?php echo _l('lead_manager_client'); ?></span></button>
              </div>
              <div id="contacts">
                <ul id="lead-contacts" class="tabcontent">
                  <?php
                  $first_lead = '';
                  if (isset($leads) && !empty($leads)) {
                    foreach ($leads as $lead) {
                      if (is_whats_app_enable($lead['id'], 'leads')) {
                        if (!is_numeric($first_lead)) {
                          $first_lead = $lead['id'];
                        }
                        $last_conversation = get_last_message_conversation_whatsapp($lead['id'], ['is_client' => 'no']);
                  ?>
                        <li class="contact" onclick="loadContentWhatsApp(<?php echo $lead['id']; ?>);" id="<?php echo $lead['id'] . '_contact'; ?>">
                          <div class="wrap">
                            <img src="<?php echo base_url('assets/images/user-placeholder.jpg'); ?>" alt="lead" />
                            <?php if (isset($last_conversation->sms_date) && !empty($last_conversation->sms_date)) { ?>
                              <p class="pull-right last_active"><?php echo isset($last_conversation->sms_date) ? time_ago($last_conversation->sms_date) : ''; ?></p>
                            <?php } else { ?>
                              <p class="pull-right last_active"><?php echo isset($last_conversation->added_at) ? time_ago($last_conversation->added_at) : ''; ?></p>
                            <?php } ?>
                            <div class="meta">
                              <p class="name"><?php echo $lead['name']; ?></p>
                              <small><?php echo isset($lead['phonenumber']) && !empty($lead['phonenumber']) ? $lead['phonenumber'] : _l('NA'); ?></small>
                              <?php if (isset($last_conversation) && !empty($last_conversation)) { ?>
                                <p class="preview"><?php echo isset($last_conversation->sms_direction) && $last_conversation->sms_direction == 'outgoing' ? _l('lm_wa_by_you_title') : _l('lm_wa_by_lead_title'); ?><?php echo isset($last_conversation->sms_body) && $last_conversation->is_files ? _l('lm_wa_file_title') : $last_conversation->sms_body; ?></p>
                              <?php } ?>
                              <div class="count_unread_div">
                                <?php
                                $count_unread_sms = get_total_unread_whatsapp_sms($lead['id'], ['is_client' => 'no', 'to_id' => get_staff_user_id()]);
                                if ($count_unread_sms > 0) {
                                  echo "<small class='count_unread'>" . $count_unread_sms . "</small>";
                                }
                                ?>
                              </div>
                            </div>
                          </div>
                        </li>
                  <?php }
                    }
                  } ?>
                </ul>
                <ul id="client-contacts" class="tabcontent hidden">
                  <?php
                  if (isset($clients) && !empty($clients)) {
                    foreach ($clients as $client) {
                      $primary_contact_id = get_primary_contact_user_id($client['userid']);
                      if (is_whats_app_enable($primary_contact_id, 'customers')) {
                        if (isset($primary_contact_id) && !empty($primary_contact_id)) {
                          $profile_image = contact_profile_image_url($primary_contact_id);
                          $last_conversation = get_last_message_conversation($client['userid'], ['is_client' => 'yes']);
                  ?>
                          <li class="contact" onclick="loadContentWhatsApp(<?php echo $client['userid']; ?>);" id="<?php echo $client['userid'] . '_contact'; ?>">
                            <div class="wrap">
                              <img src="<?php echo $profile_image; ?>" alt="contact" />
                              <?php if (isset($last_conversation->sms_date) && !empty($last_conversation->sms_date)) { ?>
                                <p class="pull-right last_active"><?php echo isset($last_conversation->sms_date) ? time_ago($last_conversation->sms_date) : ''; ?></p>
                              <?php } else { ?>
                                <p class="pull-right last_active"><?php echo isset($last_conversation->added_at) ? time_ago($last_conversation->added_at) : ''; ?></p>
                              <?php } ?>
                              <div class="meta">
                                <p class="name"><?php echo $client['company']; ?></p>
                                <small><?php echo isset($client['phonenumber']) && !empty($client['phonenumber']) ? $client['phonenumber'] : _l('NA'); ?></small>
                                <?php if (isset($last_conversation) && !empty($last_conversation)) { ?>
                                  <p class="preview"><?php echo isset($last_conversation->sms_direction) && $last_conversation->sms_direction == 'outgoing' ? _l('lm_wa_by_you_title') : _l('lm_wa_by_client_title'); ?> : <?php echo isset($last_conversation->sms_body) && $last_conversation->is_files ? _l('lm_wa_file_title') : $last_conversation->sms_body; ?></p>
                                <?php } ?>
                                <div class="count_unread_div">
                                  <?php
                                  $count_unread_sms = get_total_unread_whatsapp_sms($lead['id'], ['is_client' => 'yes', 'to_id' => get_staff_user_id()]);
                                  if ($count_unread_sms > 0) {
                                    echo "<small class='count_unread'>" . $count_unread_sms . "</small>";
                                  }
                                  ?>
                                </div>
                              </div>
                            </div>
                          </li>
                  <?php }
                      }
                    }
                  } ?>
                </ul>
              </div>

            </div>
            <div class="content temp_conversation" id="conversation">

            </div>
          </div>
        </div>
      </div>
      <?php echo form_close(); ?>
    </div>
  </div>
</div>
<?php init_tail(); ?>
<script>
  var lmPusherKey = "<?= get_option('pusher_app_key') ?>";
  var lmAppCluster = "<?= get_option('pusher_cluster') ?>";
  var lmpusher = '';
  if (lmPusherKey !== '' && lmAppCluster !== '') {
    lmpusher = new Pusher(lmPusherKey, {
      cluster: lmAppCluster
    });
    Pusher.logToConsole = true;
  }
  var leadManagerChannel = lmpusher.subscribe('lead-manager-chanel');
  leadManagerChannel.bind('whatsapp-event', function(data) {
    var obj = JSON.parse(JSON.stringify(data));
    if ($("#" + obj.from + "_contact").hasClass('active')) {
      var msgBody = obj.message;
      if (obj.file !== undefined) {
        var filetype_arr = obj.file.filetype.split('/');
        var filetype = filetype_arr[0];
        if (filetype == 'image') {
          msgBody = '<img class="whatsapp_img_thumb" src="' + site_url + 'uploads/lead_manager/whatsapp/' + obj.file.type + '/' + obj.file.from_id + '/' + obj.file.to_id + '/' + obj.file.file_name + '">';
        } else {
          msgBody = "<i class='fa fa-file-text-o whatsapp_file_thumb'></i>";
        }
      }
      $('<li id="' + obj.sms_id + '" class="incoming"><img src="' + obj.profile_image + '" alt="" /><p>' + msgBody + '</p><small>' + obj.time + '</small><span class="sms_status">' + obj.sms_status + '</span></li>').appendTo($('.messages ul'));
      document.querySelector(".messages").scrollTop = document.querySelector(".messages").scrollHeight;
    }
  });
  leadManagerChannel.bind('whatsapp-status-event', function(data) {
    var obj = JSON.parse(JSON.stringify(data));
    $('li#' + obj.id).find('span.sms_status').text(obj.sms_status);
  });
  selectedLeadId = "<?php echo $first_lead; ?>";
  $(function() {
    loadContentWhatsApp(selectedLeadId);
    $(document).on("click", "li.wh_contact", function(e) {
      loadContentWhatsApp($(this).data('contactid'));
    });
  });
  $(document).on('click', 'button.submit', function(event) {
    selectedLeadId = $(event.target).data('lead');
    var type = $(event.target).data('type');
    if (selectedLeadId && type) {
      newWhatsappMessageOutgoing(selectedLeadId, type, $(event.target));
    } else {
      alert_float("danger", "something went wrong plz refresh the page!");
    }
  });
  $(window).on('keypress', function(event) {
    if (event.which == 13) {
      var _button = $(event.target).closest('div.wrap').find('button.submit');
      selectedLeadId = _button.data('lead');
      var type = _button.data('type');
      if (selectedLeadId && type) {
        newWhatsappMessageOutgoing(selectedLeadId, type, _button);
      } else {
        alert("something went wrong plz refresh the page!");
        return false;
      }
    }
  });

  function serachContactsFilter(event) {
    var type = event.target.getAttribute('ctype');
    var name = $("#serch-input").val();
    $.post(admin_url + 'lead_manager/serch_contacts_by_filter', {
      name: name,
      type: type,
      filter_by: event.target.value,
      is_whatsapp: true
    }, function(response) {
      $('#contacts ul#' + type + '-contacts').html(response);
    });
  }
</script>