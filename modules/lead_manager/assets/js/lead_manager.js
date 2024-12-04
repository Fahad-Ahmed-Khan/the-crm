"use-strict"
var id = $('#lead-modals').find('input[name="leadid"]').val(),
    table_lead_manager = $('table.table-lead-managerd'),
    selectedLeadId = '',
    interval = null,
    whatsappDropzone = null,
    table_whatsapp_templates = $('table.table-whatsapp_templates'),
    LeadsManagerServerParams = {},
    lead_manager_table_api;
$(function() {
    validate_sms_form();
    var table_whatsapp_templates_api = initDataTable(table_whatsapp_templates, admin_url + 'lead_manager/setup/whatsapp_templates_table', [], [], [], [table_whatsapp_templates.find('#th-id').index(), 'desc']);
})

function lead_manager_mark_as(status_id, lead_id) {
    if (status_id == '1') {
        requestGet('lead_manager/get_convert_data/' + lead_id).done(function(response) {
            $('#lead_convert_to_customer').html(response);
            $('#convert_lead_to_client_modal').modal({
                show: true,
                backdrop: 'static',
                keyboard: false
            });
        }).fail(function(data) {
            alert_float('danger', data.responseText);
        }).always(function() {
            console.log('always');
            return false;
        })
    } else {
        var data = {};
        data.status = status_id;
        data.leadid = lead_id;
        $.post(admin_url + 'lead_manager/update_lead_status', data).done(function(response) {
            table_lead_manager.DataTable().ajax.reload(null, false);
        });
    }
}
if (table_lead_manager.length) {
    var tableLeadsConsentHeading = table_lead_manager.find('#th-consent');
    var manageLeadsTableNotSortable = [0];
    var manageLeadsTableNotSearchable = [0, table_lead_manager.find('#th-assigned').index()];

    if (tableLeadsConsentHeading.length > 0) {
        manageLeadsTableNotSortable.push(tableLeadsConsentHeading.index());
        manageLeadsTableNotSearchable.push(tableLeadsConsentHeading.index());
    }

    lead_manager_table_api = initDataTable(table_lead_manager, admin_url + 'lead_manager/table', manageLeadsTableNotSearchable, manageLeadsTableNotSortable, LeadsManagerServerParams, [table_lead_manager.find('th.date-created').index(), 'desc']);

    if (lead_manager_table_api && tableLeadsConsentHeading.length > 0) {
        lead_manager_table_api.on('draw', function() {
            var tableData = table_lead_manager.find('tbody tr');
            $.each(tableData, function() {
                $(this).find('td:eq(3)').addClass('bg-light-gray');
            });
        });
    }
}
$("body").on('click', 'table.dataTable tbody td:first-child', function() {
    var tr = $(this).parents('tr');
    if ($(this).parents('table').DataTable().row(tr).child.isShown()) {
        var switchBox = $(tr).next().find('input.onoffswitch-checkbox');
        if (switchBox.length > 0) {
            var switchBoxId = Math.random().toString(16).slice(2);
            switchBox.attr('id', switchBoxId).next().attr('for', switchBoxId);
        }
    }
});

function leadManagerActivity(lead_id) {
    let data = { 'id': lead_id };
    let url = admin_url + 'lead_manager/activity_log';
    let modalContentSpace = $("#lead-manager-activity-modal").find(".modal-content")
    $.get(url, data, function(resp) {
        modalContentSpace.html(resp);
        $("#lead-manager-activity-modal").modal("show");
    })
}

function zoomMeetingDetails(meeting_id) {
    let data = { 'id': meeting_id };
    let url = admin_url + 'lead_manager/zoom_meeting/zoomMeetingDetails';
    let modalContentSpace = $("#lead-manager-meeting-details").find(".modal-content")
    $.get(url, data, function(resp) {
        modalContentSpace.html(resp);
        $("#lead-manager-meeting-details").modal("show");
    })
}

function zoomMeetingDetailsUpdate(meeting_id) {
    let data = { 'id': meeting_id };
    let url = admin_url + 'lead_manager/zoom_meeting/zoomMeetingDetailsUpdate';
    let modalContentSpace = $("#lead-manager-meeting-details").find(".modal-content")
    $.get(url, data, function(resp) {
        modalContentSpace.html(resp);
        $("#lead-manager-meeting-details").modal("show");
    })
}

function validate_sms_form(lead_id) {
    var messages = {};
    var validationObject = { message: 'required' };
    var form = $('#sms-form-' + lead_id);
    appValidateForm(form, validationObject, sms_form_handler, messages);
}

function sms_form_handler(form) {
    form = $(form);
    var data = form.serialize();
    var leadid = $('#lead-modals').find('input[name="lm_leadid"]').val();
    $.post(form.attr('action'), data).done(function(response) {
        response = JSON.parse(response);
        if (response.error) {
            alert_float('danger', response.error);
        }
        if (response.success) {
            alert_float('success', 'Message Sent!');
            if ($.fn.DataTable.isDataTable('.table-lead-managerd')) {
                form.trigger("reset")
                $("#lead-manager-sms-modal").modal('hide');
                $('.table-lead-managerd').DataTable().ajax.reload(null, false);
            }

        }
    }).fail(function(data) {
        alert_float('danger', data.responseText);
        return false;
    });
    return false;
}
$('body').on('click', 'button.send_sms_btn_lm', function() {
    let lead_id = $(this).attr('data-lead');
    $('form#sms-form-' + lead_id).submit();
});

$("body").on('submit', '#lead-manager-meeting-remark #meeting_remark_form', function() {
    var form = $(this);
    var $leadModal = $('#lead-manager-meeting-remark');
    var data = $(form).serialize();
    $.post(form.attr('action'), data).done(function(response) {
        response = JSON.parse(response);
        $leadModal.modal('hide');
        alert_float('success', "Remark  Save successfully.");
        table_lead_manager.DataTable().ajax.reload(null, false);
    }).fail(function(data) {
        alert_float('danger', "something Wrong");
    });
    return false;
});

function leadManagerMessage(lead_id) {
    let data = { 'id': lead_id };
    let url = admin_url + 'lead_manager/send_sms_modal';
    let modalContentSpace = $("#lead-manager-sms-modal").find(".modal-content")
    $.get(url, data, function(resp) {
        modalContentSpace.html(resp);
        $("#lead-manager-sms-modal").modal("show");
    })
}

function saveMeetingRemark(id, rel_type) {
    let data = { 'id': id, 'rel_type': rel_type };
    let url = admin_url + 'lead_manager/zoom_meeting/show_remark_modal';
    let modalContentSpace = $("#lead-manager-meeting-remark").find(".modal-content")
    $.get(url, data, function(resp) {
        modalContentSpace.html(resp);
        $("#lead-manager-meeting-remark").modal("show");
    })
}

function showMeetingRemark(id, rel_type) {
    let data = { 'id': id, 'rel_type': rel_type };
    let url = admin_url + 'lead_manager/zoom_meeting/showMeetingRemark';
    let modalContentSpace = $("#lead-manager-meeting-show_remark").find(".modal-content")
    $.get(url, data, function(resp) {
        modalContentSpace.html(resp);
        $("#lead-manager-meeting-show_remark").modal("show");
    })
}

function leadManagerZoom(lead_id) {
    let data = { 'id': lead_id };
    let url = admin_url + 'lead_manager/send_zoom_link_modal';
    let modalContentSpace = $("#lead-manager-zoom-modal").find(".modal-content")
    $.get(url, data, function(resp) {
        if (resp == 'email not found!') {
            alert('Plz add email id to lead!')
        } else {
            modalContentSpace.html(resp);
            $("#lead-manager-zoom-modal").modal("show");
        }
    })
}

function lead_manager_bulk_sms_actions(event) {
    if (confirm_delete()) {
        var ids = [];
        var data = {};
        var rows = $('.table-lead-managerd').find('tbody tr');
        $.each(rows, function() {
            var checkbox = $($(this).find('td').eq(0)).find('input');
            if (checkbox.prop('checked') === true) {
                ids.push(checkbox.val());
            }
        });
        if (ids.length > 0) {
            data.ids = ids;
            data.message = $('#bulk_message_content').val();
            $(event).addClass('disabled');
            setTimeout(function() {
                $.post(admin_url + 'lead_manager/bulk_action', data).done(function(data) {
                    alert_float('success', 'Message Sent!');
                    if ($.fn.DataTable.isDataTable('.table-lead-managerd')) {
                        $("#lead_manager_bulk_actions").modal('hide');
                        $('.table-lead-managerd').DataTable().ajax.reload(null, false);
                    }
                }).fail(function(data) {
                    $('#lead_manager_bulk_actions').modal('hide');
                    alert_float('danger', data.responseText);
                });
            }, 2000);
        } else {
            alert_float('danger', 'No lead selected!');
        }
    }
}
$("body").on('submit', '#lead-manager-zoom-modal #zoom_meeting_form', function() {
    var form = $(this);
    var $leadModal = $('#lead-manager-zoom-modal');
    var data = $(form).serialize();
    $.post(form.attr('action'), data).done(function(response) {
        response = JSON.parse(response);
        $leadModal.modal('hide');
        alert_float('success', "Meeting  scheduled successfully. Please check email id for more details.");
    }).fail(function(data) {
        alert_float('danger', "something Wrong");
    });
    return false;
});

/*conversations*/

$("#profile-img").click(function() {
    $("#status-options").toggleClass("active");
});

$(".expand-button").click(function() {
    $("#profile").toggleClass("expanded");
    $("#contacts").toggleClass("expanded");
});

$("#status-options ul li").click(function() {
    $("#profile-img").removeClass();
    $("#status-online").removeClass("active");
    $("#status-away").removeClass("active");
    $("#status-busy").removeClass("active");
    $("#status-offline").removeClass("active");
    $(this).addClass("active");

    if ($("#status-online").hasClass("active")) {
        $("#profile-img").addClass("online");
    } else if ($("#status-away").hasClass("active")) {
        $("#profile-img").addClass("away");
    } else if ($("#status-busy").hasClass("active")) {
        $("#profile-img").addClass("busy");
    } else if ($("#status-offline").hasClass("active")) {
        $("#profile-img").addClass("offline");
    } else {
        $("#profile-img").removeClass();
    };

    $("#status-options").removeClass("active");
});

function newMessageOutgoing(lead_id, type, _button) {
    message = $(".message-input input").val();
    if ($.trim(message) == '') {
        $(".message-input input").focus();
        alert_float('danger', 'Write your message please!');
        return false;
    }
    _button.prop('disabled', true).html('<i class="fa fa-spinner fa-pulse"></i> ');
    $.post(admin_url + 'lead_manager/send_sms', { lm_leadid: lead_id, is_client: type, message: message }, function(response) {
        var obj = jQuery.parseJSON(response)
        if (obj.success) {
            $('<li id="' + obj.sms_id + '" class="outgoing"><img src="' + obj.profile_image + '" alt="" /><p>' + message + '</p><small>' + obj.time + '</small><span class="sms_status">' + obj.sms_status + '</span></li>').appendTo($('.messages ul'));
            $('.message-input input[type="text"]').val(null);
            $('.contact.active .preview').html('<span>You: </span>' + message);
            document.querySelector(".messages").scrollTop = document.querySelector(".messages").scrollHeight;
            serachContacts($("#serch-input"));
        } else {
            alert_float('danger', obj.error);
            $(".message-input input").val("");
        }
        _button.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> ');
    });
};

function loadContent(lead_id) {
    selectedLeadId = lead_id;
    var is_client = $("#serch-input").attr('ctype');
    $(".contact").removeClass('active');
    $("ul#" + is_client + "-contacts").find("#" + lead_id + "_contact").addClass('active');
    if (lead_id) {
        $.post(admin_url + 'lead_manager/load_conversation', { lead_id: lead_id, is_client: is_client }, function(response) {
            $("#conversation").html(response);
            $("#conversation").find("div.messages").removeClass("temp_msg");
            document.querySelector(".messages").scrollTop = document.querySelector(".messages").scrollHeight;
            $("div#contacts").find('ul#' + is_client + '-contacts li#' + lead_id + '_contact').find('div.meta').find('div.count_unread_div').html('');
            get_total_sms_unread_count();
            // setTimeout(function() {
            //     $('.loader-bg').fadeToggle();
            // }, 3000);
        });
    }
}

function newMessageIncoming() {
    var lead_id = selectedLeadId;
    var type = $("#serch-input").attr('ctype');
    last_conversation_id = $('ul#messages-ul li:last-child').attr('id');
    $.post(admin_url + 'lead_manager/incoming_sms', { lm_leadid: lead_id, last_sms_id: last_conversation_id, is_client: type }, function(response) {
        var obj = jQuery.parseJSON(response)
        if (obj.success) {
            $('<li id="' + obj.sms_id + '" class="incoming"><img src="' + obj.profile_image + '" alt="" /><p>' + obj.message + '</p><small>' + obj.time + '</small><span class="sms_status">' + obj.sms_status + '</span></li>').appendTo($('.messages ul'));
            document.querySelector(".messages").scrollTop = document.querySelector(".messages").scrollHeight;
        }
        $("div#contacts").find('ul#' + type + '-contacts li#' + lead_id + '_contact').find('div.meta').find('div.count_unread_div').html("");
    });
}

function serachContacts(event, whatsapp = false) {
    var type = $(event).attr('ctype');
    var loadedType = $("#messages-ul").data('usertype');
    var loadedId = $("#messages-ul").data('userid');
    $.post(admin_url + 'lead_manager/serch_contacts_by_name', { name: $(event).val(), type: $(event).attr('ctype'), is_whatsapp: whatsapp }, function(response) {
        $('#contacts ul#' + type + '-contacts').html(response);
        if (loadedType == type) {
            $("#" + loadedId + "_contact").addClass('active');
        }
    });
}

function leadManagerClientZoom(contact_id) {
    let data = { 'id': contact_id, 'is_client': 1 };
    let url = admin_url + 'lead_manager/send_zoom_link_modal';
    let modalContentSpace = $("#lead-manager-zoom-modal").find(".modal-content")
    $.get(url, data, function(resp) {
        if (resp == 'email not found!') {
            alert('Plz add email id to lead!')
        } else {
            modalContentSpace.html(resp);
            $("#lead-manager-zoom-modal").modal("show");
        }
    })
}

function leadManagerClientMessage(e) {

    let data = { 'id': $(e).data('contact_id'), 'is_client': 1, 'clientid': $(e).data('client_id') };
    let url = admin_url + 'lead_manager/send_sms_modal';
    let modalContentSpace = $("#lead-manager-sms-modal").find(".modal-content")
    $.get(url, data, function(resp) {
        modalContentSpace.html(resp);
        $("#lead-manager-sms-modal").modal("show");
    })
}

function leadManagerClientActivity(lead_id) {
    let data = { 'id': lead_id, 'is_client': 1 };
    let url = admin_url + 'lead_manager/activity_log';
    let modalContentSpace = $("#lead-manager-activity-modal").find(".modal-content")
    $.get(url, data, function(resp) {
        modalContentSpace.html(resp);
        $("#lead-manager-activity-modal").modal("show");
    })
}

function openContactTab(type, whatsapp = false) {
    $("#serch-input").val("");
    if (type == 'lead-contacts') {
        $("#btn-leads").addClass('active_btn');
        $("#btn-clients").removeClass('active_btn');
        $("#client-contacts").addClass('hidden');
        $("#" + type).removeClass('hidden');
        $("#serch-input").attr('ctype', 'lead');
        $("#filter-select").attr('ctype', 'lead');
    } else {
        $("#btn-clients").addClass('active_btn');
        $("#btn-leads").removeClass('active_btn');
        $("#lead-contacts").addClass('hidden');
        $("#" + type).removeClass('hidden');
        $("#serch-input").attr('ctype', 'client');
        $("#filter-select").attr('ctype', 'client');
    }
    serachContacts($("#serch-input"), whatsapp);
}

function incoming_unread_sms() {
    var ids = [];
    var type = $("#serch-input").attr('ctype');
    $("div#contacts").find('ul#' + type + '-contacts li').each(function(i) {
        ids.push($(this).attr('id').slice(0, -8));
    });
    $.post(admin_url + 'lead_manager/incoming_sms_nofify', { ids: JSON.stringify(ids), is_client: type }, function(response) {
        var obj = jQuery.parseJSON(response)
        if (obj.success) {
            if (obj.data.length > 0) {
                $.each(obj.data, function(index, value) {
                    $("div#contacts").find('ul#' + type + '-contacts li#' + value.from_id + '_contact').find('div.meta').find('div.count_unread_div').html("<small class='count_unread'>" + value.total + "</small>");
                })
            }
        }
    });
}

function leadManagerMailbox(leadid) {
    if (leadid) {
        tinymce.remove();
        requestGet('lead_manager/get_mail_box_compose/' + leadid).done(function(response) {
            $('#lead-manager-mail-modal').html(response);
            $('#lead-manager-mail-modal').modal({
                show: true,
                backdrop: 'static',
                keyboard: false
            });
            tinymce.init({
                selector: "textarea"
            });
        }).fail(function(data) {
            alert_float('danger', data.responseText);
        }).always(function() {
            console.log('always');
            return false;
        })
    } else {
        alert_float('danger', 'lead id not found!');
    }
}

function loadMailboxTable(e, direction = '', status) {
    $(".customer-profile-group-heading").html($(e).data('tab'));
    $(e).parent('li').siblings().removeClass("active");
    $(e).parent('li').addClass('active');
    if (direction != "") {
        $("input[name='direction']").val(direction);
    } else {
        $("input[name='direction']").val("");
    }
    $("input[name='status']").val(status);
    var serverParams = {};
    $.each($('._hidden_inputs._filters input'), function() {
        serverParams[$(this).attr('name')] = '[name="' + $(this).attr('name') + '"]';
    });
    $(".table-lm-mailbox").DataTable().ajax.reload(null, false);
    $("#tab-content-form").addClass('hide');
    $("#tab-content-table").removeClass('hide');
    if (direction == 'outbound' && status == 'sending') {
        mailboxTable.column(1).visible(false);
        mailboxTable.column(2).visible(true);
    } else {
        mailboxTable.column(1).visible(true);
        mailboxTable.column(2).visible(false);
    }
}

function loadMailboxSetting(e) {
    $(e).parent('li').siblings().removeClass("active");
    $(e).parent('li').addClass('active');
    requestGet('lead_manager/get_mail_box_configuration').done(function(response) {
        $("#tab-content-form").html(response);
        $("#tab-content-table").addClass('hide');
        $("#tab-content-form").removeClass('hide');
        $("#smtp_encryption").selectpicker();
        $("#imap_encryption").selectpicker();
    }).fail(function(data) {
        alert_float('danger', 'access denied!');
    });
}
$("body").on('change', '#mass_select_all_lm', function() {
    var to, rows, checked;
    to = 'lead-managerd';
    rows = $('.table-' + to).find('tbody tr');
    checked = $(this).prop('checked');
    $.each(rows, function() {
        $($(this).find('td').eq(0)).find('input').prop('checked', checked);
    });
});

function submitMailboxConfig(e) {
    if (!confirm('Are you sure!')) {
        return false;
    }
    $("#mailbox-config-form").validate({
        rules: {
            smtp_server: "required",
            smtp_encryption: "required",
            smtp_user: "required",
            smtp_password: "required",
            imap_server: "required",
            imap_encryption: "required",
            imap_user: "required",
            imap_password: "required"
        }
    });
    if ($("#mailbox-config-form").valid()) {
        $(e).html('Please wait...');
        $(e).prop('disabled', true);
        var data = $("#mailbox-config-form").serialize();
        $.post(admin_url + 'lead_manager/get_mail_box_configuration', data).done(function(response) {
            response = $.parseJSON(response);
            if (response.status) {
                alert_float('success', response.responseText);
            } else {
                alert_float('danger', response.responseText);
            }
            $(e).prop('disabled', true);
            $(e).html('Save');
            $(e).prop('disabled', false);
        }).fail(function(data) {
            alert_float('danger', 'something went wrong!');
        })
    }
}

function loadMailboxCompose(e) {
    $(e).parent('li').siblings().removeClass("active");
    $(e).parent('li').addClass('active');
    requestGet('lead_manager/get_mail_box_compose').done(function(response) {
        $("#tab-content-form").html(response);
        tinymce.remove();
        tinymce.init({ selector: "textarea" });
        $("#tab-content-table").addClass('hide');
        $("#tab-content-form").removeClass('hide');
    }).fail(function(data) {
        alert_float('danger', 'access denied!');
    });
}

function submitMailboxCompose(e) {
    tinyMCE.triggerSave()
    $("#mailbox-compose-form").validate({
        rules: {
            to: "required",
            subject: "required",
            message: "required"
        }
    });
    if ($("#mailbox-compose-form").valid()) {
        $(e).html('Please wait...');
        $(e).prop('disabled', true);
        var formData = new FormData($('#mailbox-compose-form')[0]);
        $.ajax({
            type: "POST",
            url: admin_url + 'lead_manager/sendEmailMailbox',
            data: formData,
            processData: false,
            contentType: false,
            success: function(resp) {
                resp = JSON.parse(resp);
                alert_float(resp.status, resp.message);
                $("#mailbox-compose-form")[0].reset();
                $(e).html('Send');
                $(e).prop('disabled', false);
            }
        });
    }
}

function viewMailBoxMail(id) {
    window.location.href = admin_url + 'lead_manager/view_email/' + id;
}

function validate_mailbox_email_reply_form() {
    tinyMCE.triggerSave()
    var messages = {};
    var validationObject = {
        message: 'required',
    };
    appValidateForm($('#mailbox-reply-form'), validationObject, lead_mailbox_email_reply_handler, messages);
}

function validate_mailbox_email_forword_form() {
    var messages = {};
    var validationObject = {
        to: 'required',
        message: 'required',
    };
    appValidateForm($('#mailbox-forword-form'), validationObject, lead_mailbox_email_reply_handler, messages);
}

function lead_mailbox_email_reply_handler(form) {
    form = $(form);
    // var data = form.serialize();
    var formData = new FormData(form[0]);
    // $.post(form.attr('action'), data).done(function(response) {
    //     response = JSON.parse(response);
    //     if (response.message !== '') {
    //         alert_float('success', response.message);
    //         form.trigger("reset")
    //         $('#reply-mailbox-btn').removeClass('disabled');
    //         $('#forword-mailbox-btn').removeClass('disabled');
    //         $('#reply-mailbox-btn').html('Send');
    //         $('#forword-mailbox-btn').html('Send');
    //     }
    // }).fail(function(data) {
    //     alert_float('danger', data.responseText);
    //     $('#reply-mailbox-btn').removeClass('disabled');
    //     $('#forword-mailbox-btn').removeClass('disabled');
    //     $('#reply-mailbox-btn').html('Send');
    //     $('#forword-mailbox-btn').html('Send');
    //     return false;
    // });
    $.ajax({
        type: "POST",
        url: form.attr('action'),
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            // $("#mailbox-compose-form").trigger("reset");
            // if (resp) {
            //     alert_float('success', 'Saved as a draft!');
            // } else {
            //     alert_float('danger', 'Failed to save as a draft!');
            // }
            // $(e).prop('disabled', false);
            if (response.message !== '') {
                response = JSON.parse(response);
                alert_float('success', response.message);
                form.trigger("reset")
                $('#reply-mailbox-btn').removeClass('disabled');
                $('#forword-mailbox-btn').removeClass('disabled');
                $('#reply-mailbox-btn').html('Send');
                $('#forword-mailbox-btn').html('Send');
            } else {
                alert_float('danger', response.responseText);
                $('#reply-mailbox-btn').removeClass('disabled');
                $('#forword-mailbox-btn').removeClass('disabled');
                $('#reply-mailbox-btn').html('Send');
                $('#forword-mailbox-btn').html('Send');
            }
        }
    });
    return false;
}
$(document).on('click', '#reply-mailbox-btn', function() {
    validate_mailbox_email_reply_form();
});
$(document).on('click', '#forword-mailbox-btn', function() {
    validate_mailbox_email_forword_form();
});
$(document).on('click', '#reply-mailbox-link', function() {
    $("#reply-div").toggleClass('hide');
    if ($("#forword-div").is(":visible")) {
        $("#forword-div").toggleClass('hide');
    }
    if ($("#message-div").is(":visible")) {
        $("#message-div").toggleClass('hide');
    }
});
$(document).on('click', '#forword-mailbox-link', function() {
    $("#forword-div").toggleClass('hide');
    if ($("#reply-div").is(":visible")) {
        $("#reply-div").toggleClass('hide');
    }
    if ($("#message-div").is(":visible")) {
        $("#message-div").toggleClass('hide');
    }
});

function lm_mb_bulk_inbox(param, _table) {
    if (confirm_delete()) {
        var ids = [];
        var data = {};
        var rows = $(_table).find('tbody tr');
        $.each(rows, function() {
            var checkbox = $($(this).find('td').eq(0)).find('input');
            if (checkbox.prop('checked') === true) {
                ids.push(checkbox.val());
            }
        });
        if (ids.length > 0) {
            data.ids = ids;
            data.action = param;
            $.post(admin_url + 'lead_manager/mailbox_mark_as_bulk', data).done(function(resp) {
                resp = JSON.parse(resp);
                $(_table).DataTable().ajax.reload(null, false);
                alert_float(resp.status, resp.responseText);
            }).fail(function(resp) {
                resp = JSON.parse(resp);
                $(_table).DataTable().ajax.reload(null, false);
                alert_float('danger', resp.responseText);
            });
        } else {
            alert_float('danger', 'please select rows!');
        }
    }
}

function submitMailboxComposeSaveAsDraft(e) {
    tinyMCE.triggerSave()
    $("#mailbox-compose-form").validate({
        rules: {
            to: "required",
            subject: "required",
            message: "required"
        }
    });
    if ($("#mailbox-compose-form").valid()) {
        $(e).prop('disabled', true);
        var formData = new FormData($('#mailbox-compose-form')[0]);
        $.ajax({
            type: "POST",
            url: admin_url + 'lead_manager/DraftEmailMailbox',
            data: formData,
            processData: false,
            contentType: false,
            success: function(resp) {
                $("#mailbox-compose-form").trigger("reset");
                if (resp) {
                    alert_float('success', 'Saved as a draft!');
                } else {
                    alert_float('danger', 'Failed to save as a draft!');
                }
                $(e).prop('disabled', false);
            }
        });
    }
}

function lm_mb_single_inbox(e, param, _table) {
    if (confirm_delete()) {
        var data = {};
        param = $(e).data('action');
        data.ids = $(e).data('id');
        data.action = param;
        _table = $(e).data('table');
        $.post(admin_url + 'lead_manager/mailbox_mark_as_single', data).done(function(resp) {
            resp = JSON.parse(resp);
            $(_table).DataTable().ajax.reload(null, false);
            alert_float(resp.status, resp.responseText);
        }).fail(function(resp) {
            resp = JSON.parse(resp);
            $(_table).DataTable().ajax.reload(null, false);
            alert_float('danger', resp.responseText);
        });
    }
}
$(document).on('click', '#lm-send-email-btn', function(e) {
    var form = $('#mailbox-compose-form');
    form.validate({
        rules: {
            to: "required",
            subject: "required",
            message: "required"
        }
    });
    if (form.valid()) {
        $(e).html('Please wait...');
        $(e).prop('disabled', true);
        tinyMCE.triggerSave();
        var formData = new FormData(form[0]);
        $.ajax({
            type: "POST",
            url: admin_url + 'lead_manager/sendEmailMailbox',
            data: formData,
            processData: false,
            contentType: false,
            success: function(resp) {
                resp = JSON.parse(resp);
                $(e).html('Send');
                $(e).prop('disabled', false);
                $('#lead-manager-mail-modal').modal('hide');
                alert_float(resp.status, resp.message);
            }
        });
    } else {
        $(e).html('Send');
        $(e).prop('disabled', false);
        $(e).removeClass('disabled');
    }
});

function lm_mb_view_mail_action(e) {
    if (confirm_delete()) {
        var ids = [];
        var data = {};
        var param = $(e).data('param');
        ids.push($(e).data('id'));
        if (ids.length > 0) {
            data.ids = ids;
            data.action = param;
            $.post(admin_url + 'lead_manager/mailbox_mark_as_bulk', data).done(function(resp) {
                resp = JSON.parse(resp);
                alert_float(resp.status, resp.responseText);
                if (param == 'star') {
                    $(e).data('param', 'unstar');
                    $(e).html('<i class="fa fa-star text-warning" aria-hidden="true"></i>');
                } else if (param == 'unstar') {
                    $(e).data('param', 'star');
                    $(e).html('<i class="fa fa-star-o text-muted" aria-hidden="true"></i>');
                } else if (param == 'bookmark') {
                    $(e).data('param', 'unbookmark');
                    $(e).html('<i class="fa fa-bookmark text-muted" aria-hidden="true"></i>');
                } else if (param == 'unbookmark') {
                    $(e).data('param', 'bookmark');
                    $(e).html('<i class="fa fa-bookmark-o" aria-hidden="true"></i>');
                } else if (param == 'delete') {
                    window.location.href = admin_url + "lead_manager/mailbox";
                }
            }).fail(function(resp) {
                resp = JSON.parse(resp);
                alert_float('danger', resp.responseText);
            });
        } else {
            alert_float('danger', 'Something went wrong!');
        }
    }
}
$(document).on('click', '#draft-mailbox-btn', function() {
    validate_mailbox_email_draft_form();
});

function validate_mailbox_email_draft_form() {
    var messages = {};
    var validationObject = {
        to: 'required',
        subject: 'required',
        message: 'required',
    };
    appValidateForm($('#mailbox-draft-form'), validationObject, lead_mailbox_email_draft_handler, messages);
}

function lead_mailbox_email_draft_handler(form) {
    form = $(form);
    var data = form.serialize();
    $.post(form.attr('action'), data).done(function(response) {
        response = JSON.parse(response);
        if (response.message !== '') {
            alert_float('success', response.message);
            form.trigger("reset")
            $('#draft-mailbox-btn').removeClass('disabled');
            $('#draft-mailbox-btn').html('Send');
            window.location.href = admin_url + "lead_manager/mailbox?dir=outbound&st=draft";
        }
    }).fail(function(data) {
        alert_float('danger', data.responseText);
        $('#draft-mailbox-btn').removeClass('disabled');
        $('#draft-mailbox-btn').html('Send');
        return false;
    });
    return false;
}

function loadContentWhatsApp(lead_id) {
    selectedLeadId = lead_id;
    var is_client = $("#serch-input").attr('ctype');
    $(".contact").removeClass('active');
    $("ul#" + is_client + "-contacts").find("#" + lead_id + "_contact").addClass('active');
    if (lead_id) {
        $.post(admin_url + 'lead_manager/load_conversation_whatsapp', { lead_id: lead_id, is_client: is_client }, function(response) {
            $("#conversation").html(response);
            $("#conversation").removeClass("temp_conversation");
            $("div#contacts").find('ul#' + is_client + '-contacts li#' + lead_id + '_contact').find('div.meta').find('div.count_unread_div').html('');
            document.querySelector(".messages").scrollTop = document.querySelector(".messages").scrollHeight;
            get_total_whatsapp_unread_count();
            whatsappDropZoneReady();
            $(".attachment_btn").click(function() {
                $("#whatsapp-files-upload").toggle("slow");
            });
        });
    }
}

function newWhatsappMessageOutgoing(lead_id, type, _button) {
    message = $(".message-input input[type='text']").val();
    if ($.trim(message) == '') {
        $(".message-input input[type='text']").focus();
        alert_float('danger', 'Write your message please!');
        return false;
    }
    _button.prop('disabled', true).html('<i class="fa fa-spinner fa-pulse"></i> ');
    $.post(admin_url + 'lead_manager/send_whatsapp_sms', { lm_leadid: lead_id, is_client: type, message: message }, function(response) {
        var obj = JSON.parse(response);
        if (obj.success) {
            $('<li id="' + obj.sms_id + '" class="outgoing"><img src="' + obj.profile_image + '" alt="" /><p>' + message + '</p><small>' + obj.time + '</small><span class="sms_status">' + obj.sms_status + '</span></li>').appendTo($('.messages ul'));
            $('.message-input input[type="text"]').val(null);
            $('.contact.active .preview').html('<span>You: </span>' + message);
            document.querySelector(".messages").scrollTop = document.querySelector(".messages").scrollHeight;
        } else {
            alert_float('danger', obj.error);
            $(".message-input input").val("");
        }
        _button.prop('disabled', false).html('<i class="fa fa-paper-plane" data-lead="' + lead_id + '" data-type="' + type + '"></i> ');
    });
};

function whatsappDropZoneReady() {
    if ($('#whatsapp-files-upload').length > 0) {
        whatsappDropzone = new Dropzone('#whatsapp-files-upload', appCreateDropzoneOptions({
            paramName: "file",
            //uploadMultiple: false,
            //parallelUploads: 20,
            //maxFiles: 20,
            //previewsContainer: '.dropzone-previews',
            //addRemoveLinks: true,
            accept: function(file, done) {
                done();
            },
            success: function(file, response) {
                if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                    var obj = JSON.parse(response);
                    $('<li id="' + obj.response.sms_id + '" class="outgoing"><img src="' + obj.response.profile_image + '" alt="" /><p><img class="whatsapp_img_thumb" src="' + site_url + 'uploads/lead_manager/whatsapp/' + obj.media_record.type + '/' + obj.media_record.from_id + '/' + obj.media_record.to_id + '/' + obj.media_record.file_name + '"></p><small>' + obj.response.time + '</small><span class="sms_status">' + obj.response.sms_status + '</span></li>').appendTo($('.messages ul'));
                    $('.message-input input[type="text"]').val(null);
                    $('.contact.active .preview').html('<span>You: File</span>');
                    $("#whatsapp-files-upload").toggle("slow");
                    document.querySelector(".messages").scrollTop = document.querySelector(".messages").scrollHeight;
                }
            },
            sending: function(file, xhr, formData) {
                console.log(xhr);
            }
        }));
    }
}

function newWhatsappMessageOutgoingFirst(_button) {
    // var message = "Xin chào bạn!\nAd xin phép được hỗ trợ bạn tại đây ạ.\nBạn cần mua hàng hay sao ạ?",
    lead_id = $(_button).data('id'),
        type = $(_button).data('type');
    $(_button).prop('disabled', true).html('<i class="fa fa-spinner fa-pulse"></i> ');
    $.post(admin_url + 'lead_manager/send_whatsapp_sms', { lm_leadid: lead_id, is_client: type }, function(response) {
        var obj = JSON.parse(response);
        if (obj.success) {
            $('<li id="' + obj.sms_id + '" class="outgoing"><img src="' + obj.profile_image + '" alt="" /><p>' + obj.message + '</p><small>' + obj.time + '</small><span class="sms_status">' + obj.sms_status + '</span></li>').appendTo($('.messages ul'));
            $('.contact.active .preview').html('<span>You: </span>' + obj.message);
            $(".messages").animate({
                scrollTop: $('.messages').get(0).scrollHeight
            }, 2000)
            $(".messages").removeClass("start_chat");
        } else {
            alert_float('danger', obj.error);
            $(_button).attr('disabled', false).html('Start Chat <i class="fa-brands fa-whatsapp"></i> ');
        }
    });
};

function new_user_token() {
    appValidateForm($('form'), {
        staff_id: 'required',
        expiration_date: 'required'
    });
    $('#user_token_api').modal('show');
    $('.edit-title').addClass('hide');

    $('#user_token_api input[name="staff_id"]').val('');
    $('#user_token_api input[name="expiration_date"]').val('');
}

function edit_user_token(invoker, id) {
    //console.log(`invoker:${$(invoker).data('staff_id')}`);
    appValidateForm($('form'), {
        staff_id: 'required',
        expiration_date: 'required'
    });
    var staff_id = $(invoker).data('staff_id');
    var expiration_date = $(invoker).data('expiration_date');
    $('#additional').append(hidden_input('id', id));
    $('#staff_id option[value="' + staff_id + '"]').prop("selected", true);
    $('.selectpicker').selectpicker('refresh')
    console.log($('#staff_id option[value="' + staff_id + '"]').text());
    $('#user_token_api input[name="expiration_date"]').val(expiration_date);
    $('#user_token_api').modal('show');
    $('.add-title').addClass('hide');
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