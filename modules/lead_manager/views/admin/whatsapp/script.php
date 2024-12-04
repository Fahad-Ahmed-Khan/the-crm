<?php defined('BASEPATH') or exit('No direct script access allowed');
if (get_option('pusher_realtime_notifications') == 1) { ?>
     <script>
          $(function() {
               var pusher = new Pusher("<?php echo get_option('pusher_app_key'); ?>", {
                    cluster: "<?php echo get_option('pusher_cluster'); ?>"
               });
               var channel = pusher.subscribe('lead-manager-notifications-channel-<?php echo get_staff_user_id(); ?>');
               Pusher.logToConsole = false;
               channel.bind('whatsapp_notification', function(data) {
                    fetch_whatsapp_notifications(data);
                    get_total_whatsapp_unread_count();
               });
               get_total_whatsapp_unread_count();
               channel.bind('sms_notification', function(data) {
                    fetch_sms_notifications(data);
                    get_total_sms_unread_count();
               });
               get_total_sms_unread_count();
               channel.bind('mailbox_notification', function(data){
                    if ($(".sub-menu-item-lead_manager_mailbox").find('span.badge').length > 0) {
                         $(".sub-menu-item-lead_manager_mailbox").find('span.badge').html(data.unread);
                    } else {
                         $(".sub-menu-item-lead_manager_mailbox").append('<span class="badge menu-badge bg-warning" data-toggle="tooltip" title="Unread mails">' + data.unread + '</span>');
                    }
                    $(".table-lm-mailbox").DataTable().ajax.reload(null, false);
               });
               get_total_mail_unread_count();
          });

          function fetch_whatsapp_notifications(params) {
               requestGetJSON('lead_manager/whatsapp_notifications').done(function(response) {
                    if (window.location.href.indexOf("whatsapp") > -1) {
                         $.each(response, function(key, value) {
                              if (value.length > 0) {
                                   $.each(value, function(k, v) {
                                        if ($("#btn-" + key).hasClass('active_btn')) {
                                             $("div#contacts").find('ul#' + key.slice(0, -1) + '-contacts li#' + v.from_id + '_contact').find('div.meta').find('div.count_unread_div').html("<small class='count_unread'>" + v.unread + "</small>");
                                             if ($("div#contacts").find('ul#' + key.slice(0, -1) + '-contacts li#' + v.from_id + '_contact').hasClass('active')) {
                                                  $.post(admin_url + 'lead_manager/update_incoming_whatsapp_sms', {
                                                       lm_leadid: v.from_id,
                                                       last_sms_id: v.last_sms_id,
                                                       is_client: key == 'leads' ? '0' : '1'
                                                  }, function(res) {
                                                       res = JSON.parse(res);
                                                       if (res.success) {
                                                            $("div#contacts").find('ul#' + key.slice(0, -1) + '-contacts li#' + v.from_id + '_contact').find('div.meta').find('div.count_unread_div').html('');
                                                       }
                                                  });
                                             }
                                        }

                                   });
                              }
                         });
                    }
               })
          }

          function get_total_whatsapp_unread_count() {
               requestGetJSON('lead_manager/whatsapp_total_unread').done(function(response) {
                    if ($(".sub-menu-item-lead_manager_whatsapp").find('span.badge').length > 0) {
                         $(".sub-menu-item-lead_manager_whatsapp").find('span.badge').html(response.total);
                    } else {
                         $(".sub-menu-item-lead_manager_whatsapp").append('<span class="badge menu-badge bg-success" data-toggle="tooltip" title="Unread messages">' + response.total + '</span>');
                    }
               });
          }

          function get_total_sms_unread_count() {
               requestGetJSON('lead_manager/sms_total_unread').done(function(response) {
                    if ($(".sub-menu-item-lead_manager_chats").find('span.badge').length > 0) {
                         $(".sub-menu-item-lead_manager_chats").find('span.badge').html(response.total);
                    } else {
                         $(".sub-menu-item-lead_manager_chats").append('<span class="badge menu-badge bg-secondary" data-toggle="tooltip" title="Unread messages">' + response.total + '</span>');
                    }
               });
          }

          function fetch_sms_notifications(data) {
               if (window.location.href.indexOf("chats") > -1) {
                    var response = data.messages;
                    $.each(response, function(key, value) {
                         if (value.length > 0) {
                              $.each(value, function(k, v) {
                                   if ($("#btn-" + key).hasClass('active_btn')) {
                                        $("div#contacts").find('ul#' + key.slice(0, -1) + '-contacts li#' + v.from_id + '_contact').find('div.meta').find('div.count_unread_div').html("<small class='count_unread'>" + v.unread + "</small>");
                                        if ($("div#contacts").find('ul#' + key.slice(0, -1) + '-contacts li#' + v.from_id + '_contact').hasClass('active')) {
                                             $.post(admin_url + 'lead_manager/update_incoming_sms', {
                                                  lm_leadid: v.from_id,
                                                  last_sms_id: v.last_sms_id,
                                                  is_client: key == 'leads' ? '0' : '1'
                                             }, function(res) {
                                                  res = JSON.parse(res);
                                                  if (res.success) {
                                                       $("div#contacts").find('ul#' + key.slice(0, -1) + '-contacts li#' + v.from_id + '_contact').find('div.meta').find('div.count_unread_div').html('');
                                                  }
                                             });
                                        }
                                   }

                              });
                         }
                    });
               }
          }
          function get_total_mail_unread_count(){
               requestGetJSON('lead_manager/email_total_unread').done(function(response) {
                    if ($(".sub-menu-item-lead_manager_mailbox").find('span.badge').length > 0) {
                         $(".sub-menu-item-lead_manager_mailbox").find('span.badge').html(response.total);
                    } else {
                         $(".sub-menu-item-lead_manager_mailbox").append('<span class="badge menu-badge bg-warning" data-toggle="tooltip" title="Unread mails">' + response.total + '</span>');
                    }
                    $(".table-lm-mailbox").DataTable().ajax.reload(null, false);
               });
          }
     </script>
<?php } ?>