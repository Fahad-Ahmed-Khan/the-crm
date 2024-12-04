<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="container ui-widget-content" id="soft-phone-draggable">
    <div class="call ringing -ringing" id="ringing-soft-phone">
        <div class="head_bell">
            <img src="<?php echo base_url('modules/lead_manager/assets/icons/bell.svg'); ?>">
        </div>
        <div class="details" id="calling-info">
            Incomming call...
            <p>Zonvoir</p>
        </div>

        <ul class="actions">
            <li class="action pic"> <a id="accept" href="javascript:void(0);"><i class="fa fa-phone"></i></a></li>
            <li class="action cut"> <a id="refuse" href="javascript:void(0);"><i class="fa fa-phone decline-icon-"></i></a></li>
        </ul>
    </div>

    <!--SOft Phone speaking--->

    <div class="call speaking flipback" id="speaking-soft-phone">
        <div class="head_bell">
            <div class="sound"><span class="fa-stack"><i class="fa fa-microphone fa-stack-1x"></i><i class="fa fa-ban fa-stack-1x hidden"></i></span></div>
        </div>
        <div class="details" id="caller-info">
            <h4><i class="fa fa-phone"></i> - Zonvoir</h4>
        </div>

        <div class="call_duration">
            <span id="hours">00 :</span>
            <span id="mins">00 :</span>
            <span id="seconds">00</span>
        </div>

        <div id="dialer_modal">
            <div class="modal-body">
                <table id="dialer_table">
                    <tr>
                        <td id="dialer_input_td" colspan="3">
                            <input type="text" placeholder="">
                            <buttton id="add-cnf-call-btn" class="btn btn-primary hidden"><i class="fa fa-phone"></i></buttton>
                        </td>
                    </tr>
                    <tr class="dialer_num_tr">
                        <td class="dialer_num" onclick="dialerClick('dial', 1)">
                            <div>
                                1
                            </div>
                        </td>
                        <td class="dialer_num" onclick="dialerClick('dial', 2)">
                            <div>
                                2
                            </div>
                        </td>
                        <td class="dialer_num" onclick="dialerClick('dial', 3)">
                            <div>
                                3
                            </div>
                        </td>
                    </tr>
                    <tr class="dialer_num_tr">
                        <td class="dialer_num" onclick="dialerClick('dial', 4)">
                            <div>
                                4
                            </div>
                        </td>
                        <td class="dialer_num" onclick="dialerClick('dial', 5)">
                            <div>
                                5
                            </div>
                        </td>
                        <td class="dialer_num" onclick="dialerClick('dial', 6)">
                            <div>
                                6
                            </div>
                        </td>
                    </tr>
                    <tr class="dialer_num_tr">
                        <td class="dialer_num" onclick="dialerClick('dial', 7)">
                            <div>
                                7
                            </div>
                        </td>
                        <td class="dialer_num" onclick="dialerClick('dial', 8)">
                            <div>
                                8
                            </div>
                        </td>
                        <td class="dialer_num" onclick="dialerClick('dial', 9)">
                            <div>
                                9
                            </div>
                        </td>
                    </tr>
                    <tr class="dialer_num_tr">
                        <td class="dialer_num" onclick="dialerClick('dial', '*')">
                            <div>
                                *
                            </div>
                        </td>
                        <td class="dialer_num" onclick="dialerClick('dial', 0)">
                            <div>
                                0
                            </div>
                        </td>
                        <td class="dialer_num" onclick="dialerClick('dial', '#')">
                            <div>
                                #
                            </div>
                        </td>
                    </tr>

                    <tr class="dialer_num_tr">
                        <td class="dialer_del_td">
                            <img alt="clear" onclick="dialerClick('clear', 'clear')" src="data:image/svg+xml;base64,PHN2ZyBhcmlhLWhpZGRlbj0idHJ1ZSIgZm9jdXNhYmxlPSJmYWxzZSIgZGF0YS1wcmVmaXg9ImZhcyIgZGF0YS1pY29uPSJlcmFzZXIiIHJvbGU9ImltZyIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgNTEyIDUxMiIgY2xhc3M9InN2Zy1pbmxpbmUtLWZhIGZhLWVyYXNlciBmYS13LTE2IGZhLTd4Ij48cGF0aCBmaWxsPSIjYjFiMWIxIiBkPSJNNDk3Ljk0MSAyNzMuOTQxYzE4Ljc0NS0xOC43NDUgMTguNzQ1LTQ5LjEzNyAwLTY3Ljg4MmwtMTYwLTE2MGMtMTguNzQ1LTE4Ljc0NS00OS4xMzYtMTguNzQ2LTY3Ljg4MyAwbC0yNTYgMjU2Yy0xOC43NDUgMTguNzQ1LTE4Ljc0NSA0OS4xMzcgMCA2Ny44ODJsOTYgOTZBNDguMDA0IDQ4LjAwNCAwIDAgMCAxNDQgNDgwaDM1NmM2LjYyNyAwIDEyLTUuMzczIDEyLTEydi00MGMwLTYuNjI3LTUuMzczLTEyLTEyLTEySDM1NS44ODNsMTQyLjA1OC0xNDIuMDU5em0tMzAyLjYyNy02Mi42MjdsMTM3LjM3MyAxMzcuMzczTDI2NS4zNzMgNDE2SDE1MC42MjhsLTgwLTgwIDEyNC42ODYtMTI0LjY4NnoiIGNsYXNzPSIiPjwvcGF0aD48L3N2Zz4=" width="22px" title="Clear" />
                        </td>
                        <td class="dialer_num" onclick="dialerClick('dial', '+')">
                            <div>
                                +
                            </div>
                        </td>
                        <td class="dialer_del_td">
                            <img alt="delete" onclick="dialerClick('delete', 'delete')" src="data:image/svg+xml;base64,PHN2ZyBhcmlhLWhpZGRlbj0idHJ1ZSIgZm9jdXNhYmxlPSJmYWxzZSIgZGF0YS1wcmVmaXg9ImZhciIgZGF0YS1pY29uPSJiYWNrc3BhY2UiIHJvbGU9ImltZyIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB2aWV3Qm94PSIwIDAgNjQwIDUxMiIgY2xhc3M9InN2Zy1pbmxpbmUtLWZhIGZhLWJhY2tzcGFjZSBmYS13LTIwIGZhLTd4Ij48cGF0aCBmaWxsPSIjREMxQTU5IiBkPSJNNDY5LjY1IDE4MS42NWwtMTEuMzEtMTEuMzFjLTYuMjUtNi4yNS0xNi4zOC02LjI1LTIyLjYzIDBMMzg0IDIyMi4wNmwtNTEuNzItNTEuNzJjLTYuMjUtNi4yNS0xNi4zOC02LjI1LTIyLjYzIDBsLTExLjMxIDExLjMxYy02LjI1IDYuMjUtNi4yNSAxNi4zOCAwIDIyLjYzTDM1MC4wNiAyNTZsLTUxLjcyIDUxLjcyYy02LjI1IDYuMjUtNi4yNSAxNi4zOCAwIDIyLjYzbDExLjMxIDExLjMxYzYuMjUgNi4yNSAxNi4zOCA2LjI1IDIyLjYzIDBMMzg0IDI4OS45NGw1MS43MiA1MS43MmM2LjI1IDYuMjUgMTYuMzggNi4yNSAyMi42MyAwbDExLjMxLTExLjMxYzYuMjUtNi4yNSA2LjI1LTE2LjM4IDAtMjIuNjNMNDE3Ljk0IDI1Nmw1MS43Mi01MS43MmM2LjI0LTYuMjUgNi4yNC0xNi4zOC0uMDEtMjIuNjN6TTU3NiA2NEgyMDUuMjZDMTg4LjI4IDY0IDE3MiA3MC43NCAxNjAgODIuNzRMOS4zNyAyMzMuMzdjLTEyLjUgMTIuNS0xMi41IDMyLjc2IDAgNDUuMjVMMTYwIDQyOS4yNWMxMiAxMiAyOC4yOCAxOC43NSA0NS4yNSAxOC43NUg1NzZjMzUuMzUgMCA2NC0yOC42NSA2NC02NFYxMjhjMC0zNS4zNS0yOC42NS02NC02NC02NHptMTYgMzIwYzAgOC44Mi03LjE4IDE2LTE2IDE2SDIwNS4yNmMtNC4yNyAwLTguMjktMS42Ni0xMS4zMS00LjY5TDU0LjYzIDI1NmwxMzkuMzEtMTM5LjMxYzMuMDItMy4wMiA3LjA0LTQuNjkgMTEuMzEtNC42OUg1NzZjOC44MiAwIDE2IDcuMTggMTYgMTZ2MjU2eiIgY2xhc3M9IiI+PC9wYXRoPjwvc3ZnPg==" width="25px" title="Delete" />
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <div id="transfer_staff_modal">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-11">
                        <?php echo render_select('transfer_staff', $staffs, ['staffid', 'full_name']); ?>
                    </div>
                    <div class="col-md-1">
                        <a id="transfer-btn" class="" href="javascript:void(0);">
                            <i class="fa fa-exchange"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <ul class="action_dial">
            <li class="action cut">
                <a id="drop" href="javascript:void(0);" title="<?php echo _l('lm_call_disconnect'); ?>">
                    <i class="fa fa-phone decline-icon">
                    </i>
                </a>
            </li>
            <!-- <li class="action">
                <a id="hold-btn" class="_hold" href="javascript:void(0);" title="<?php echo _l('lm_call_hold'); ?>">
                    <i class="fa fa-pause"></i>
                </a>
            </li> -->
            <!-- <li class="action">
                <a id="call-transfer-btn" class="" href="javascript:void(0);" title="<?php echo _l('lm_call_transefer'); ?>">
                    <i class="fa fa-exchange"></i>
                </a>
            </li> -->
            <li class="action">
                <a id="confrence-btn" class="_confrence" href="javascript:void(0);" title="<?php echo _l('lm_call_conference'); ?>">
                    <i class="fa fa-users"></i>
                </a>
            </li>
            <li class="action">
                <a id="dial_pad" href="javascript:void(0);" title="<?php echo _l('lm_call_dialpad'); ?>">
                    <i class="fa fa-th"></i>
                </a>
            </li>
        </ul>
        <div class="conference_list hidden">
            <ul></ul>
        </div>
        <div class="incomming_list hidden">
            <ul>
            </ul>
        </div>
    </div>
    <!--Close SOft Phone speaking--->

    <div class="call dialing -dialing" id="dialing-soft-phone">
        <div class="head_bell">
            <img src="<?php echo base_url('modules/lead_manager/assets/icons/bell.svg'); ?>">
        </div>
        <div class="details" id="calling-info">Connecting<img src="<?php echo base_url('modules/lead_manager/assets/icons/calling.gif'); ?>"> <span>+1123456789</span></div>
        <ul class="actions">
            <li class="action_dial cut"> <a id="disconnect" href="javascript:void(0);"><i class="fa fa-phone decline-icon"></i></a></li>
        </ul>
    </div>
</div>
<input type="hidden" value="<?php echo $staffPhoneNumber; ?>" id="staffPhoneNumber" name="staffPhoneNumber">
<input type="hidden" value="<?php echo has_permission('lead_manager', '', 'show_contact'); ?>" id="showContact" name="showContact">
<?php echo strval($staffPhone); ?>