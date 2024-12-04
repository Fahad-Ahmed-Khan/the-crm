"use strict";
var staffPhoneNumber = $("#staffPhoneNumber").val();
var incomingCallFrom = '';
var holdStatus = false;
var confrenceStatus = false;
var device = null;
var _connection = null;
var hours = 0;
var mins = 0;
var seconds = 0;
var timex = null;
var callProgress = false;
var acceptCall = function(phoneNumber) {
    $(".ringing").removeClass("-ringing");
    setTimeout(function() {
        $(".ringing").addClass("-flip");
        $("#speaking-soft-phone").addClass("show_cut_btn");
        $(".speaking").removeClass("flipback");
    }, 0);
    phoneNumber = $("#showContact").val() ? phoneNumber : '******' + String(phoneNumber).slice(-4);
    $("#caller-info").html(phoneNumber);
};
var acceptedDialledCall = function(phoneNumber) {
    $(".dialing").removeClass("-dialing");
    setTimeout(function() {
        $(".dialing").addClass("-flip");
        $("#speaking-soft-phone").addClass("show_cut_btn");
        $(".speaking").removeClass("flipback");
    }, 0);
    $("#caller-info").html(phoneNumber);
};

$("#refuse").click(function() {
    $(".ringing").removeClass("-ringing");
    $(".ringing").addClass("-drop");

    setTimeout(function() {
        $(".ringing").addClass("-fadeout");
    }, 0);

    setTimeout(function() {
        $(".ringing").addClass("-ringing").removeClass("-fadeout");
    }, 10000);
});

var dropLiveCall = function() {
    if ($('#hold-btn').hasClass('hold')) {
        $('#hold-btn').removeClass('hold');
        $('#hold-btn').addClass('_hold');
    }
    $('#hold-btn').show();
    resetTimer();
    holdStatus = false;
    confrenceStatus = false;
    $(".speaking").addClass("-drop");
    setTimeout(function() {
        $("#speaking-soft-phone").removeClass("show_cut_btn");
        $(".ringing").removeClass("-flip");
        $(".speaking")
            .addClass("flipback")
            .removeClass("hold")
            .removeClass("-drop");
    }, 2000);
    $("#confrence-btn").removeClass("confrence").addClass('_confrence');
    $(".conference_list").find('ul').html("");
    callProgress = false;
    //location.reload();
};

var dropRingingCall = function() {
    $(".ringing").addClass("-drop");
    setTimeout(function() {
        $(".ringing").removeClass("-drop");
        $(".ringing").css('display', '');
    }, 2000);
};

var onMuteChange = function(muted) {
    if (muted) {
        $(".fa-ban").addClass("hidden");
        $("#eq").removeClass("hidden");
    } else {
        $(".fa-ban").removeClass("hidden");
        $("#eq").addClass("hidden");
    }
};

var onHoldClick = function(e, callSid = '') {
    if (e.hasClass("_hold")) {
        $("#confrence-btn").addClass('hidden');
        $.post(site_url + 'lead_manager/call_control/holdCall', { CallSid: callSid }, function(resp) {
            holdStatus = true;
        });
        $('#hold-btn').removeClass('_hold');
        $('#hold-btn').addClass('hold');
    } else {
        $("#confrence-btn").removeClass('hidden');
        $.post(site_url + 'lead_manager/call_control/unholdCall', { CallSid: callSid }, function(resp) {
            holdStatus = false;
        });
        $('#hold-btn').removeClass('hold');
        $('#hold-btn').addClass('_hold');
        $('#hold-btn').hide();
    }
}
var onClickConfrence = function(e, callSid) {
    if (e.hasClass("_confrence")) {
        $("#hold-btn").closest('li.action').addClass('hidden');
        $.post(site_url + 'lead_manager/call_control/confrenceCall', { CallSid: callSid, callerIdNumber: staffPhoneNumber }, function(resp) {
            resp = JSON.parse(resp);
            confrenceStatus = resp.status;
            $("#speaking-soft-phone").find("#caller-info").html("Confrence call");
            var html = '<li> <div><i class="fa fa-user"></i>  ' + resp.number + '</div><div class="conference_list_action" data-callsid="' + resp.callSid + '" data-friendlyname="' + resp.status + '"> <span onclick="updateConfrence(this);" data-action="mute"><i class="fa fa-microphone" ></i></span><span onclick="updateConfrence(this);" data-action="hold"><i class="fa fa-pause"></i></span><span class="cnfrc_decline" onclick="deleteConfrence(this);"><i class="fa fa-phone decline-icon"></i></span></div></li>';
            $("#speaking-soft-phone").find('.conference_list ul').append(html);
            $(".conference_list").removeClass("hidden");
        });
        e.removeClass("_confrence").addClass('confrence');
    } else {
        $("#hold-btn").closest('li.action').removeClass('hidden');
        dropLiveCall();
    }
}

$(function() {
    $("#soft-phone-draggable").draggable();
});

function dialPhone(e) {
    if (confrenceStatus) {
        $.post(admin_url + 'lead_manager/call_control/addConfParticep', {
            "callerIdNumber": $(e).data('from'),
            "phoneNumber": $(e).data('to'),
            "freindlyName": confrenceStatus
        }).done(function(data) {
            data = JSON.parse(data);
            var html = '<li> <div><i class="fa fa-user"></i>  ' + data.number + '</div><div class="conference_list_action" data-callsid="' + data.callSid + '" data-friendlyname="' + confrenceStatus + '"> <span onclick="updateConfrence(this);" data-action="mute"><i class="fa fa-microphone" ></i></span><span onclick="updateConfrence(this);" data-action="hold"><i class="fa fa-pause"></i></span><span class="cnfrc_decline" onclick="deleteConfrence(this);"><i class="fa fa-phone decline-icon"></i></span></div></li>';
            $("#speaking-soft-phone").find('.conference_list ul').append(html);
            $(".conference_list").removeClass("hidden");
        }).fail(function() {
            updateCallStatus("Could not get a token from server!");
        });
    } else {
        if (!$(e).data('to')) {
            alert_float('danger', 'Callee number is invalid!');
        } else if (!$(e).data('from')) {
            alert_float('danger', 'Staff Twilio number is invalid!');
        } else {
            let params = { "phoneNumber": $(e).data('to'), "leadId": $(e).data('id'), "callerIdNumber": $(e).data('from'), "is_lead": $(e).data('is_lead'), "client_id": $(e).data('client_id') };
            device.connect(params);
            let phone = $("#showContact").val() ? $(e).data('to') : '******' + String($(e).data('to')).slice(-4);
            $("#dialing-soft-phone").find('.details span').text(phone);
            $("#dialing-soft-phone").show();
        }
    }
}
$("#disconnect").click(function() {
    dropLiveCall();
    $("#dialing-soft-phone").css('display', '');
})

function updateCallStatus(status) {
    console.log(status);
}

function setupClient() {
    $.post(admin_url + 'lead_manager/generateClientToken', {
        forPage: window.location.pathname,
    }).done(function(data) {
        device = new Twilio.Device();
        let obj = JSON.parse(data);
        device.setup(obj.token, { debug: true, allowIncomingWhileBusy: true, closeProtection: true });
        setupHandlers(device);
    }).fail(function() {
        updateCallStatus("Could not get a token from server!");
    });
}

function setupHandlers(device) {
    device.on('ready', function(_device) {
        updateCallStatus("Ready");
    });
    device.on('error', function(error) {
        alert_float('danger', 'Error: ' + error.message);
        $("#dialing-soft-phone").css("display", "none");
        dropLiveCall();
        if (error.code == '31205' || error.code == '31204') {
            location.reload();
        }
    });
    device.on('connect', function(connection) {
        callProgress = true;
        _connection = connection;
        setTimeout(function() {
            if (connection.status() == "open") {
                startTimer();
                updateCallStatus("In call with " + connection.message.phoneNumber);
                acceptedDialledCall(connection.message.phoneNumber);
            }
        }, 1000);
        $(".sound").click(function() {
            let mute = connection.isMuted();
            connection.mute(mute ? false : true);
            onMuteChange(mute);
        });
        $("#drop").click(function() {
            connection.disconnect();
            dropLiveCall();
            dropRingingCall();
            $("#dialer_modal").hide();
        });
        $("#hold-btn").click(function() {
            var progressCallSid = connection.parameters.CallSid;
            onHoldClick($(this), progressCallSid)
        });
        $("#confrence-btn").click(function() {
            onClickConfrence($(this), connection.parameters.CallSid)
        });
    });
    device.on('disconnect', function(connection) {
        dropLiveCall();
        updateCallStatus("Ready");
    });

    device.on('incoming', function(connection) {
        let callerId = connection.parameters.From;
        let callSid = connection.parameters.CallSid;
        let acallerId = connection.parameters.To;
        _connection = connection;
        startTimer();
        getFromNumberByChildCallSid(callSid, staffPhoneNumber);
        connection.accept(function() {
            callProgress = true;
            updateCallStatus("In call with customer " + incomingCallFrom);
        });
        connection.reject(function() {
            dropLiveCall();
            updateCallStatus("incoming Call Rejected!");
        });
        connection.ignore(function() {
            dropRingingCall();
            updateCallStatus("incoming Call Ignored!");
        });
        connection.disconnect(function() {
            dropLiveCall();
            dropRingingCall();
            updateCallStatus("incoming Call disconnected");
        });
        $("#accept").click(function() {
            connection.accept();
            acceptCall(incomingCallFrom);
        });
        $("#refuse").click(function() {
            connection.reject();
            dropRingingCall();
            updateCallStatus("incoming Call Ignored by agent");
        });
    });
}

function getFromNumberByChildCallSid(childSid, staffNumber) {
    $.post(admin_url + 'lead_manager/call_control/getFromNumberByChildCallSid', {
        'CallSid': childSid,
    }).done(function(resp) {
        resp = JSON.parse(resp)
        if (staffNumber === resp.to) {
            incomingCallFrom = resp.from;
            $("#ringing-soft-phone").find('#calling-info p').html(resp.from);
            $("#ringing-soft-phone").show();
        }
    }).fail(function() {
        console.log('failed!')
    });
}

function getFromNumberByChildCallSid2(childSid) {
    var result = null;
    $.ajax({
        type: "POST",
        url: admin_url + 'lead_manager/call_control/getFromNumberByChildCallSid',
        data: ({ 'CallSid': childSid }),
        async: false,
        success: function(data) {
            result = JSON.parse(data);
        },
        error: function() {
            alert('Error occured');
        }
    });
    return result;
}
$(document).ready(function() {
    setupClient();
})

function dialerClick(type, value) {
    let input = $('#dialer_input_td input');
    let input_val = $('#dialer_input_td input').val();
    if (type == 'dial') {
        if (!confrenceStatus) {
            if (_connection) {
                _connection.sendDigits(String(value));
            }
        } else {
            if ($("#add-cnf-call-btn").hasClass('hidden')) {
                $("#add-cnf-call-btn").toggleClass('hidden');
            }
        }
        input.val(String(input_val + value));
    } else if (type == 'delete') {
        input.val(input_val.substring(0, input_val.length - 1));
    } else if (type == 'clear') {
        input.val("");
    }
}
$("#dial_pad").click(function() {
    $("#dialer_modal").toggle();
});
$("#call-transfer-btn").click(function() {
    $("#transfer_staff_modal").toggle();
});

function startTimer() {
    timex = setTimeout(function() {
        // var callStatus = null;
        seconds++;
        if (seconds > 59) {
            seconds = 0;
            mins++;
            if (mins > 59) {
                mins = 0;
                hours++;
                if (hours < 10) {
                    $("#hours").text('0' + hours + ':')
                } else $("#hours").text(hours + ':');
            }
            if (mins < 10) {
                $("#mins").text('0' + mins + ':');
            } else $("#mins").text(mins + ':');
        }
        if (seconds < 10) {
            $("#seconds").text('0' + seconds);
        } else {
            $("#seconds").text(seconds);
        }
        startTimer();
    }, 1000);
}

function resetTimer() {
    clearTimeout(timex);
    //var callStatus = null;
    hours = 0;
    mins = 0;
    seconds = 0;
    $('#hours').html('00:');
    $('#mins').html('00:');
    $('#seconds').html('00');
};

function deleteConfrence(e) {
    $.post(site_url + 'lead_manager/call_control/deleteConferanceCall', { freindlyName: $(e).closest('div.conference_list_action').data('friendlyname'), callsid: $(e).closest('div.conference_list_action').data('callsid') }, function(resp) {
        resp ? $(e).closest('li').remove() : alert('Something went wrong!');
    });
}

function updateConfrence(e) {
    $.post(site_url + 'lead_manager/call_control/updateConferanceCall', { freindlyName: $(e).closest('div.conference_list_action').data('friendlyname'), callsid: $(e).closest('div.conference_list_action').data('callsid'), action_type: $(e).data('action') }, function(resp) {
        resp = JSON.parse(resp)
        $(e).toggleClass(resp.type);
        $(e).data('action', resp.action);
    });
}
$("#add-cnf-call-btn").click(function() {
    $.post(admin_url + 'lead_manager/call_control/addConfParticep', {
        "callerIdNumber": $("#staffPhoneNumber").val(),
        "phoneNumber": $(this).siblings('input').val(),
        "freindlyName": confrenceStatus
    }).done(function(data) {
        data = JSON.parse(data);
        if (!('error' in data)) {
            var html = '<li> <div><i class="fa fa-user"></i>  ' + data.number + '</div><div class="conference_list_action" data-callsid="' + data.callSid + '" data-friendlyname="' + confrenceStatus + '"> <span onclick="updateConfrence(this);" data-action="mute"><i class="fa fa-microphone" ></i></span><span onclick="updateConfrence(this);" data-action="hold"><i class="fa fa-pause"></i></span><span class="cnfrc_decline" onclick="deleteConfrence(this);"><i class="fa fa-phone decline-icon"></i></span></div></li>';
            $("#speaking-soft-phone").find('.conference_list ul').append(html);
            $(".conference_list").removeClass("hidden");
            $("#dialer_modal").toggle();
        } else {
            alert_float('danger', data.error);
        }
        $("#add-cnf-call-btn").siblings('input').val('');
    }).fail(function() {
        updateCallStatus("Could not get a token from server!");
    });
});

function acceptIncomCall(e) {
    // con.accept();
    $.post(admin_url + 'lead_manager/call_control/addConfParticepIncoming', {
        "callerIdNumber": $("#staffPhoneNumber").val(),
        "phoneNumber": $(e).data('phone'),
        "freindlyName": confrenceStatus,
        "CallSid": $(e).data('callsid')
    }).done(function(data) {
        data = JSON.parse(data);
        console.log(data);
    }).fail(function() {
        updateCallStatus("Could not get a token from server!");
    });

}
$("#transfer-btn").click(function(e) {
    console.log($("#transfer_staff").val());
    $.post(site_url + 'lead_manager/call_control/transferCall', { staffid: $("#transfer_staff").val(), callerIdNumber: staffPhoneNumber, calleeNumber: _connection.message.phoneNumber }, function(resp) {
        resp = JSON.parse(resp)
        console.log(resp);
    });

})