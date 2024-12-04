<?php
defined('BASEPATH') or exit('No direct script access allowed');
$check =  __dir__;
$str = preg_replace('/\W\w+\s*(\W*)$/', '$1', $check);
$str . '/third_party/twilio-web/src/Twilio/autoload.php';

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
use app\services\imap\Imap as Lmimap;
use app\services\imap\ConnectionErrorException as Lmimapexep;
use Carbon\Carbon;
use Twilio\Exceptions\RestException;

function call_api_setting()
{
    $data['account_sid'] = get_option('call_twilio_account_sid');
    $data['auth_token'] = get_option('call_twilio_auth_token');
    $data['twilio_number'] = get_option('call_twilio_phone_number');
    $data['twiml_app_sid'] = get_option('call_twiml_app_sid');
    return $data;
}
function lead_manager_send_mail_template()
{
    $params = func_get_args();
    return lead_manager_mail_template(...$params)->send();
}

function lead_manager_mail_template($class)
{
    $CI = &get_instance();

    $params = func_get_args();

    unset($params[0]);

    $params = array_values($params);

    $path = lead_manager_get_mail_template_path($class, $params);

    if (!file_exists($path)) {
        if (!defined('CRON')) {
            show_error('Mail Class Does Not Exists [' . $path . ']');
        } else {

            return false;
        }
    }
    if (!class_exists($class, false)) {
        include_once($path);
    }
    $instance = new $class(...$params);
    return $instance;
}
function lead_manager_get_mail_template_path($class, &$params)
{
    $CI  = &get_instance();
    $dir = APP_MODULES_PATH . 'lead_manager/libraries/mails/';
    if (isset($params[0]) && is_string($params[0]) && is_dir(module_dir_path($params[0]))) {
        $module = $CI->app_modules->get($params[0]);

        if ($module['activated'] === 1) {
            $dir = module_libs_path($params[0]) . 'mails/';
        }
        unset($params[0]);
        $params = array_values($params);
    }

    return $dir . ucfirst($class) . '.php';
}

function get_zoom_status_by_id($id)
{
    $CI = &get_instance();
    if (!class_exists('lead_manager_model')) {
        $CI->load->model('lead_manager_model');
    }
    $statuses = $CI->lead_manager_model->get_zoom_statuses();
    $status = [
        'id'    => 0,
        'color' => '#333',
        'name'  => _l('End'),
        'order' => 1,
    ];
    foreach ($statuses as $s) {
        if ($s['id'] == $id) {
            $status = $s;

            break;
        }
    }
    return $status;
}

function get_latest_zoom_meeting_remark($id)
{
    if ($id) {
        $CI = &get_instance();
        $CI->db->where(['rel_id' => $id]);
        $CI->db->order_by('date', 'desc');
        $res = $CI->db->get(db_prefix() . 'lead_manager_meeting_remark')->row();
        return ($res) ? $res->remark : '...';
    }
    return false;
}

function get_staff_own_twilio_number($id = '')
{
    if (!is_numeric($id)) {
        $id = get_staff_user_id();
    }
    if ($id) {
        $CI = &get_instance();
        $twilio_result = $CI->db->get_where(db_prefix() . 'customfields', ['slug' => 'staff_twilio_phone_number', 'fieldto' => 'staff'])->row();
        if (isset($twilio_result) && !empty($twilio_result)) {
            $CI->db->select('value');
            $CI->db->where(['relid' => $id, 'fieldto' => 'staff', 'fieldid' => $twilio_result->id]);
            $res = $CI->db->get(db_prefix() . 'customfieldsvalues')->row();
            return ($res) ? $res->value : '0';
        } else {
            return '0';
        }
    }
    return false;
}
function get_staff_by_twilio_number($number)
{
    if ($number) {
        $CI = &get_instance();
        $twilio_result = $CI->db->get_where(db_prefix() . 'customfields', ['slug' => 'staff_twilio_phone_number', 'fieldto' => 'staff'])->row();
        if (isset($twilio_result) && !empty($twilio_result)) {
            $CI->db->select('relid');
            $CI->db->where(['value' => $number, 'fieldto' => 'staff']);
            $res = $CI->db->get(db_prefix() . 'customfieldsvalues')->row();
            return ($res) ? $res->relid : '0';
        } else {
            return '0';
        }
    }
    return false;
}
function busy_incoming_calls()
{
    if (get_option('call_twilio_active')) {
        $now = new DateTime();
        $todayDate = $now->format('Y-m-d');
        $dateObj = $todayDate . 'T00:00:00Z';
        $sid  = get_option('call_twilio_account_sid');
        $token  = get_option('call_twilio_auth_token');
        $i = 0;
        $data = [];
        try {
            $twilio = new Client($sid, $token);
            $calls = $twilio->calls->read(["direction" => "inbound-dial", 'startTimeAfter' => new \DateTime($dateObj)], 10);
        } catch (RestException $e) {
            log_activity('Unable to connect Twilio account! ' . $e->getMessage());
        }
        if (isset($calls) && !empty($calls)) {
            foreach ($calls as $record) {
                $staffId = get_staff_by_twilio_number($record->to);
                if ($staffId) {
                    $callDate = $record->dateCreated;
                    $data[$i]['status'] = $record->status;
                    $data[$i]['from'] = $record->from;
                    $data[$i]['direction'] = $record->direction;
                    $data[$i]['to'] = $record->to;
                    $data[$i]['sid'] = $record->sid;
                    $data[$i]['parentCallSid'] = $record->parentCallSid;
                    $data[$i]['dateCreated'] = $callDate->format('Y-m-d H:i:s');
                    $data[$i]['dateCreated1'] = $record->dateCreated;
                    $data[$i]['staff_id'] = $staffId;
                    $data[$i]['child_status'] = '';
                    $pcalls = $twilio->calls->read(["parentCallSid" => $data[$i]['sid']], 1);
                    if (isset($pcalls) && !empty($pcalls)) {
                        foreach ($pcalls as $childcall) {
                            $data[$i]['child_status'] = $childcall->status;
                        }
                    }
                    if ($data[$i]['status'] == 'busy' || $data[$i]['child_status'] == 'no-answer' || $data[$i]['status'] == 'failed' || $data[$i]['child_status'] == 'failed' || $data[$i]['child_status'] == 'busy' || $data[$i]['status'] == 'no-answer') {
                        addMissedCalls($data, $i);
                    }
                }
                $i++;
            }
        }
    }
}
function get_lead_name_by_number($number)
{
    if ($number) {
        $CI = &get_instance();
        $CI->db->select('name');
        $CI->db->where(['phonenumber' => $number]);
        $res = $CI->db->get(db_prefix() . 'leads')->row();
        return ($res) ? $res->name : false;
    }
    return false;
}
function addMissedCalls($data, $j)
{
    $CI = &get_instance();
    $CI->db->where('call_sid', $data[$j]['sid']);
    $q = $CI->db->get(db_prefix() . 'lead_manager_missed_calls');
    $leadName = get_lead_name_by_number($data[$j]['from']);
    if ($q->num_rows() == 0) {
        $insert_data = array(
            'staff_id' => $data[$j]['staff_id'],
            'call_sid' => $data[$j]['sid'],
            'staff_twilio_number' => $data[$j]['to'],
            'date' => $data[$j]['dateCreated'],
        );
        $CI->db->insert(db_prefix() . 'lead_manager_missed_calls', $insert_data);
        $notifcationArr = array(
            'isread' => 0,
            'isread_inline' => 0,
            'date' => $data[$j]['dateCreated'],
            'description' => 'You have missed call from: ' . $data[$j]['from'] . ' at ' . $data[$j]['dateCreated'],
            'fromuserid' => 0,
            'fromclientid' => 0,
            'from_fullname' => '//',
            'touserid' => $data[$j]['staff_id'],
            'link' => null,
            'additional_data' => null
        );
        if ($leadName) {
            $notifcationArr['description']  = 'You have missed call from: lead ' . $leadName . ' (' . $data[$j]['from'] . ')';
        } else {
            if (!class_exists('lead_manager_model')) {
                $CI->load->model('lead_manager/lead_manager_model');
            }
            $staffid = get_staff_by_twilio_number($data[$j]['to']);
            $lead_data = array(
                'description' => 'New Incoming call',
                'addedfrom' => $staffid,
                'address' => NULL,
                'email' => NULL,
                'phonenumber' => $data[$j]['from'],
                'name' => 'New lead',
                'status' => 2,
                'source' => 1,
            );
            $leadid = $CI->lead_manager_model->add_lead($lead_data);
            $notifcationArr['description']  = 'You have missed call from: ' . $data[$j]['from'];
        }
        $CI->db->insert(db_prefix() . 'notifications', $notifcationArr);
    }
}
function get_lead_id_by_number($number)
{
    if ($number) {
        $CI = &get_instance();
        $CI->db->select('id');
        $CI->db->where(['phonenumber' => $number]);
        $res = $CI->db->get(db_prefix() . 'leads')->row();
        return ($res) ? $res->id : false;
    }
    return false;
}
function auto_meeting_status_update()
{
    if (get_option('call_zoom_active')) {
        $CI = &get_instance();
        if (!class_exists('lead_manager_model')) {
            $CI->load->model('lead_manager/lead_manager_model');
        }
        $params['apiSecret'] = get_option('zoom_secret_key');
        $params['apiKey'] = get_option('zoom_api_key');
        $params['path'] = 'meetings';
        $meetings = $CI->lead_manager_model->zoomMeetingDetails('', ['status' => 'waiting']);
        include_once('modules/lead_manager/libraries/zoom/ZoomJwtApiWrapper.php');
        $zoom = new ZoomJwtApiWrapper($params);
        if (isset($meetings) && !empty($meetings)) {
            foreach ($meetings as $meeting) {
                $pathParams = array('meetingId' => $meeting['meeting_id']);
                $response = $zoom->doRequest('GET', '/meetings/{meetingId}', [], $pathParams, '');
                if (isset($response) && !empty($response) && isset($response['status'])) {
                    $update_data = ['id' => $meeting['id']];
                    $update_data['status'] = $response['status'];
                    $CI->lead_manager_model->update_meeting_status($update_data);
                }
            }
        }
    }
}
function get_last_message_conversation($lead_id, $where)
{
    if (is_numeric($lead_id)) {
        $CI = &get_instance();
        $staff_id = get_staff_user_id();
        $query = '';
        if ($where['is_client'] == 'no') {
            $query = $CI->db->query("SELECT * FROM " . db_prefix() . "lead_manager_conversation WHERE (to_id=" . $lead_id . " AND from_id=" . $staff_id . ") OR (to_id=" . $staff_id . " AND from_id=" . $lead_id . ") AND is_client = 0 ORDER BY id DESC LIMIT 1");
        } else {
            $query = $CI->db->query("SELECT * FROM " . db_prefix() . "lead_manager_conversation WHERE (to_id=" . $lead_id . " AND from_id=" . $staff_id . ") OR (to_id=" . $staff_id . " AND from_id=" . $lead_id . ") AND is_client = 1 ORDER BY id DESC LIMIT 1");
        }
        return $query->row();
    }
}
function get_meetings($status)
{
    $CI = &get_instance();
    $CI->db->where(['status' => $status]);
    $result = $CI->db->get(db_prefix() . 'lead_manager_zoom_meeting')->result_array();
    return $result;
}
function get_client_id_by_number($number)
{
    if ($number) {
        $CI = &get_instance();
        $CI->db->select('userid');
        $CI->db->where(['phonenumber' => $number, 'is_primary' => 1]);
        $res = $CI->db->get(db_prefix() . 'contacts')->row();
        return ($res) ? $res->userid : false;
    }
    return false;
}
function render_yes_no_option_lm($option_value, $label, $tooltip = '', $replace_yes_text = '', $replace_no_text = '', $replace_1 = '', $replace_0 = '')
{
    //die($replace_0);
    ob_start(); ?>
    <div class="form-group">
        <label for="<?php echo $option_value; ?>" class="control-label clearfix">
            <?php echo ($tooltip != '' ? '<i class="fa fa-question-circle" data-toggle="tooltip" data-title="' . _l($tooltip, '', false) . '"></i> ' : '') . _l($label, '', false); ?>
        </label>
        <div class="radio radio-primary radio-inline">
            <input type="radio" id="y_opt_1_<?php echo $label; ?>" name="settings[<?php echo $option_value; ?>]" value="1" <?php echo $option_value == $replace_1 ? 'checked' : ''; ?>>
            <label for="y_opt_1_<?php echo $label; ?>">
                <?php echo $replace_yes_text == '' ? _l('settings_yes') : $replace_yes_text; ?>
            </label>
        </div>
        <div class="radio radio-primary radio-inline">
            <input type="radio" id="y_opt_2_<?php echo $label; ?>" name="settings[<?php echo $option_value; ?>]" value="0" <?php echo $option_value == $replace_0 ? 'checked' : ''; ?>>
            <label for="y_opt_2_<?php echo $label; ?>">
                <?php echo $replace_no_text == '' ? _l('settings_no') : $replace_no_text; ?>
            </label>
        </div>
    </div>
<?php
    $settings = ob_get_contents();
    ob_end_clean();
    echo $settings;
}
function get_email_mailbox_configuration()
{
    $CI = &get_instance();
    $staff_id = get_staff_user_id();
    if (!class_exists('lead_manager_model')) {
        $CI->load->model('lead_manager/lead_manager_model');
    }
    return $CI->lead_manager_model->get_mail_box_configuration($staff_id);
}
function handle_lead_manager_mail_box_attachments_array($staffid, $mailboxid, $index_name = 'attachments')
{
    $uploaded_files = [];
    $path           = LEAD_MANAGER_MAILBOX_FOLDER . '/' . $mailboxid . '/';
    $CI             = &get_instance();
    if (
        isset($_FILES[$index_name]['name'])
        && ($_FILES[$index_name]['name'] != '' || is_array($_FILES[$index_name]['name']) && count($_FILES[$index_name]['name']) > 0)
    ) {
        if (!is_array($_FILES[$index_name]['name'])) {
            $_FILES[$index_name]['name']     = [$_FILES[$index_name]['name']];
            $_FILES[$index_name]['type']     = [$_FILES[$index_name]['type']];
            $_FILES[$index_name]['tmp_name'] = [$_FILES[$index_name]['tmp_name']];
            $_FILES[$index_name]['error']    = [$_FILES[$index_name]['error']];
            $_FILES[$index_name]['size']     = [$_FILES[$index_name]['size']];
        }
        _file_attachments_index_fix($index_name);
        for ($i = 0; $i < count($_FILES[$index_name]['name']); $i++) {
            // Get the temp file path
            $tmpFilePath = $_FILES[$index_name]['tmp_name'][$i];
            // Make sure we have a filepath
            if (!empty($tmpFilePath) && $tmpFilePath != '') {
                if (
                    _perfex_upload_error($_FILES[$index_name]['error'][$i])
                    || !_upload_extension_allowed($_FILES[$index_name]['name'][$i])
                ) {
                    continue;
                }
                _maybe_create_upload_path($path);
                $filename    = unique_filename($path, $_FILES[$index_name]['name'][$i]);
                $newFilePath = $path . $filename;
                // Upload the file into the temp dir
                if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                    array_push($uploaded_files, [
                        'file_name' => $filename,
                        'filetype'  => $_FILES[$index_name]['type'][$i],
                    ]);

                    if (is_image($newFilePath)) {
                        create_img_thumb($path, $filename);
                    }
                }
            }
        }
    }

    if (count($uploaded_files) > 0) {
        return $uploaded_files;
    }
    return false;
}
function get_lead_id_by_email($email)
{
    if ($email) {
        $CI = &get_instance();
        $CI->db->select('id');
        $CI->db->where(['email' => $email]);
        $res = $CI->db->get(db_prefix() . 'leads')->row();
        return ($res) ? $res->id : false;
    }
    return false;
}
function lead_manager_prepare_imap_email_body_html($body)
{
    $CI = &get_instance();
    $body = trim($body);
    $body = str_replace('&nbsp;', ' ', $body);
    // Remove html tags - strips inline styles also
    $body = trim(strip_html_tags($body, '<br/>, <br>, <a>'));
    // Once again do security
    $body = $CI->security->xss_clean($body);
    // Remove duplicate new lines
    $body = preg_replace("/[\r\n]+/", "\n", $body);
    // new lines with <br />
    $body = preg_replace('/\n(\s*\n)+/', '<br />', $body);
    $body = preg_replace('/\n/', '<br>', $body);
    return $body;
}
function check_lead_manager_mailbox_email_imap()
{
    $CI = &get_instance();
    if (!class_exists('lead_manager_model')) {
        $CI->load->model('lead_manager/lead_manager_model');
    }
    $CI->load->library('app_pusher');
    $check_every = get_option('lead_manager_imap_check_every');
    $last_run = get_option('lead_manager_imap_last_checked');
    $settings = $CI->lead_manager_model->get_mail_box_configuration();
    update_option('lead_manager_imap_last_checked', time());
    foreach ($settings as $setting) {
        $mail = (object) $setting;
        $last_sequence_id = $CI->lead_manager_model->get_mail_box_last_sequence($mail->staff_id);
        if ($mail->is_imap == '1') {
            if (empty($last_run) || (time() > $last_run + ($check_every * 60))) {
                $CI->load->model('spam_filters_model');
                $password = $mail->imap_password;
                if (!$password) {
                    log_activity('Failed to decrypt email integration password, navigate to Lead manager -> Mailbox -> setting and re-add the password.', $mail->staff_id);
                    continue;
                }
                $imap = new Lmimap(
                    $mail->imap_user,
                    $password,
                    $mail->imap_server,
                    $mail->imap_encryption,
                    $mail->imap_port
                );
                try {
                    $connection = $imap->testConnection();
                } catch (Lmimapexep $e) {
                    log_activity('Unable to connect IMAP Server! ' . $e->getMessage(), $mail->staff_id);
                    continue;
                }
                if (empty($mail->folder)) {
                    $mail->folder = stripos($mail->imap_server, 'outlook') !== false
                        || stripos($mail->imap_server, 'microsoft')
                        || stripos($mail->imap_server, 'office365') !== false ? 'Inbox' : 'INBOX';
                }
                $mailbox = $connection->getMailbox($mail->folder);
                $messages = NULL;
                if ($last_sequence_id) {
                    $messages = $mailbox->getMessageSequence($last_sequence_id . ':*');
                } else {
                    $messages = $mailbox->getMessages();
                }
                include_once(APPPATH . 'third_party/simple_html_dom.php');
                foreach ($messages as $message) {
                    if ($message->getNumber() > $last_sequence_id) {
                        $body = $message->getBodyHtml() ?? $message->getBodyText();
                        $html = str_get_html($body);
                        $formFields              = [];
                        $lead_form_custom_fields = [];
                        if ($html) {
                            foreach ($html->find('[id^="field_"],[id^="custom_field_"]') as $data) {
                                if (isset($data->plaintext)) {
                                    $value = strip_tags(trim($data->plaintext));
                                    if ($value && isset($data->attr['id']) && !empty($data->attr['id'])) {
                                        $formFields[$data->attr['id']] = $CI->security->xss_clean($value);
                                    }
                                }
                            }
                        }

                        foreach ($formFields as $key => $val) {
                            $field = (strpos($key, 'custom_field_') !== false ? strafter($key, 'custom_field_') : strafter($key, 'field_'));

                            if (strpos($key, 'custom_field_') !== false) {
                                $lead_form_custom_fields[$field] = $val;
                            } elseif ($CI->db->field_exists($field, db_prefix() . 'leads')) {
                                $formFields[$field] = $val;
                            }

                            unset($formFields[$key]);
                        }

                        $fromAddress = null;
                        $fromName    = null;

                        if ($message->getFrom()) {
                            $fromAddress = $message->getFrom()->getAddress();
                            $fromName    = $message->getFrom()->getName();
                        }

                        $replyTo = $message->getReplyTo();

                        if (count($replyTo) === 1) {
                            $fromAddress = $replyTo[0]->getAddress();
                            $fromName    = $replyTo[0]->getName() ?? $fromName;
                        }

                        $fromAddress = $formFields['email'] ?? $fromAddress;
                        $fromName    = $formFields['name'] ?? $fromName;

                        if (is_null($fromAddress)) {
                            //$message->markAsSeen();

                            continue;
                        }

                        $mailstatus = $CI->spam_filters_model->check($fromAddress, $message->getSubject(), $body, 'leads');

                        if ($mailstatus) {
                            //$message->markAsSeen();
                            log_activity('Lead Email Integration Blocked Email by Spam Filters [' . $mailstatus . ']');
                            continue;
                        }
                        $body = lead_manager_prepare_imap_email_body_html($body);
                        $mailTimestampImmutable = $message->getDate();
                        $mail_date = Carbon::createFromTimestamp($mailTimestampImmutable->getTimestamp(), $mailTimestampImmutable->getTimezone())->format('Y-m-d\TH:i:s');
                        $mail_data = [
                            'fromName'                           => $fromName,
                            'staffid'                            => $mail->staff_id,
                            'toid'                               => get_lead_id_by_email($fromAddress),
                            'status'                             => 'get',
                            'from_email'                         => $fromAddress,
                            'to_email'                           => $mail->imap_user,
                            'subject'                            => $message->getSubject(),
                            'direction'                          => 'inbound',
                            'message'                            => $body,
                            'is_attachment'                      => count($message->getAttachments()) > 0 ? 1 : 0,
                            'is_read'                            => $message->isSeen(),
                            'email_size'                         => $message->getHeaders()->get('size'),
                            'sequence_id'                        => $message->getNumber(),
                            'in_reply_to'                        => $message->getHeaders()->get('in_reply_to'),
                            'in_references'                      => $message->getHeaders()->get('references'),
                            'message_id'                         => $message->getHeaders()->get('message_id'),
                            'mail_date'                          => $mail_date
                        ];
                        $CI->db->insert(db_prefix() . 'lead_manager_mailbox', $mail_data);
                        $insert_id = $CI->db->insert_id();
                        if ($insert_id) {
                            //$message->markAsSeen();
                            $CI->lead_manager_model->handleLeadManagerMailboxImapAttachments($message, $insert_id, $mail->staff_id);
                            $mail_data['unread'] = total_rows(db_prefix() . 'lead_manager_mailbox', ['direction' => 'inbound', 'is_read' => 0, 'staffid' => $mail_data['staffid']]);
                            $CI->app_pusher->trigger('lead-manager-notifications-channel-' . $mail_data['staffid'], 'mailbox_notification', $mail_data);
                        }
                    }
                }
            }
        }
    }
}
function formatSizeUnits($bytes)
{
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }

    return $bytes;
}
function get_file_icons($type)
{
    $icon = '';
    if ($type == 'application/pdf') {
        $icon = '<i class="fa fa-file-pdf-o" aria-hidden="true"></i>';
    } elseif ($type == 'image/png') {
        $icon = '<i class="fa fa-picture-o" aria-hidden="true"></i>';
    } elseif ($type == 'image/jpeg') {
        $icon = '<i class="fa fa-picture-o" aria-hidden="true"></i>';
    } elseif ($type == 'application/msword') {
        $icon = '<i class="fa fa-file-word-o" aria-hidden="true"></i>';
    } elseif ($type == 'application/vnd.ms-powerpoint') {
        $icon = '<i class="fa fa-file-powerpoint-o" aria-hidden="true"></i>';
    } elseif ($type == 'application/vnd.ms-excel') {
        $icon = '<i class="fa fa-file-excel-o" aria-hidden="true"></i>';
    } else {
        $icon = '<i class="fa fa-file-o" aria-hidden="true"></i>';
    }
    return $icon;
}
function init_remark_notification()
{
    $CI = &get_instance();
    $CI->db->select('' . db_prefix() . 'lead_manager_meeting_remark.*, ' . db_prefix() . 'staff.email as s_email, ' . db_prefix() . 'staff.phonenumber as s_phone, ' . db_prefix() . 'staff.staffid,' . db_prefix() . 'leads.name as l_name');
    $CI->db->join(db_prefix() . 'leads', '' . db_prefix() . 'leads.id=' . db_prefix() . 'lead_manager_meeting_remark.rel_id');
    $CI->db->join(db_prefix() . 'staff', '' . db_prefix() . 'staff.staffid=' . db_prefix() . 'leads.assigned');
    $CI->db->where(db_prefix() . 'lead_manager_meeting_remark.is_notified', 0);
    $remarks     = $CI->db->get(db_prefix() . 'lead_manager_meeting_remark')->result_array();
    $notifiedUsers = [];
    if (isset($remarks) && !empty($remarks)) {
        foreach ($remarks as $reminder) {
            if (date('Y-m-d') == date('Y-m-d', strtotime($reminder['date']))) {
                $CI->db->where('id', $reminder['id']);
                $CI->db->update(db_prefix() . 'lead_manager_meeting_remark', [
                    'is_notified' => 1,
                ]);

                $notified = add_notification([
                    'fromcompany'     => true,
                    'touserid'        => $reminder['staffid'],
                    'description'     => 'You have added remark for lead #' . $reminder['rel_id'] . ': ' . $reminder['l_name'] . ' to contact at ' . _dt($reminder['lm_follow_up_date']),
                    'link'            => '',
                    'additional_data' => serialize([
                        'Remark - ' . strip_tags(mb_substr($reminder['remark'], 0, 50)) . '...',
                    ]),
                ]);

                if ($notified) {
                    array_push($notifiedUsers, $reminder['staffid']);
                }
                //print_r($notified); die;
                if (isset($reminder['s_phone']) && !empty($reminder['s_phone'])) {
                    $resp = lm_send_sms($reminder['s_phone'], $notified['description']);
                }
                pusher_trigger_notification($notifiedUsers);
            }
        }
    }
}
function lm_send_sms($phone, $message)
{
    $CI = &get_instance();
    $activeSmsGateway = $CI->app_sms->get_active_gateway();
    if ($activeSmsGateway != false) {
        $className = 'sms_' . $activeSmsGateway['id'];
        $message = clear_textarea_breaks($message);
        $retval = $CI->{$className}->send($phone, $message);
        return $retval;
    }
    return false;
}
function get_staff_twilio_number_has_whatsapp($id = '')
{
    if (!is_numeric($id)) {
        $id = get_staff_user_id();
    }
    if ($id) {
        $CI = &get_instance();
        $twilio_result = $CI->db->get_where(db_prefix() . 'customfields', ['slug' => 'staff_is_twilio_number_whats_app_enabled', 'fieldto' => 'staff'])->row();
        if (isset($twilio_result) && !empty($twilio_result)) {
            $CI->db->select('value');
            $CI->db->where(['relid' => $id, 'fieldto' => 'staff', 'fieldid' => $twilio_result->id]);
            $res = $CI->db->get(db_prefix() . 'customfieldsvalues')->row();
            return ($res) ? $res->value : '0';
        } else {
            return '0';
        }
    }
    return false;
}
function lead_manager_whatsapp()
{
    $CI = &get_instance();
    $pusher_options = array();
    $pusher_options['app_key'] = get_option('pusher_app_key');
    $pusher_options['app_secret'] = get_option('pusher_app_secret');
    $pusher_options['app_id'] = get_option('pusher_app_id');
    if (get_option('pusher_cluster') != '') {
        $pusher_options['cluster'] = get_option('pusher_cluster');
    }
    echo $CI->load->view('lead_manager/admin/whatsapp/script', $pusher_options, true);
}
function get_last_message_conversation_whatsapp($lead_id, $where)
{
    if (is_numeric($lead_id)) {
        $CI = &get_instance();
        $staff_id = get_staff_user_id();
        $query = '';
        if ($where['is_client'] == 'no') {
            $query = $CI->db->query("SELECT * FROM " . db_prefix() . "lead_manager_whatsapp WHERE (to_id=" . $lead_id . " AND from_id=" . $staff_id . ") OR (to_id=" . $staff_id . " AND from_id=" . $lead_id . ") AND is_client = 0 ORDER BY id DESC LIMIT 1");
        } else {
            $query = $CI->db->query("SELECT * FROM " . db_prefix() . "lead_manager_whatsapp WHERE (to_id=" . $lead_id . " AND from_id=" . $staff_id . ") OR (to_id=" . $staff_id . " AND from_id=" . $lead_id . ") AND is_client = 1 ORDER BY id DESC LIMIT 1");
        }
        return $query->row();
    }
}

function get_total_unread_whatsapp_sms($lead_id, $where)
{
    if (is_numeric($lead_id)) {
        $CI = &get_instance();
        $query = '';
        if ($where['is_client'] == 'no') {
            $query = $CI->db->query("SELECT count(*) as unread FROM " . db_prefix() . "lead_manager_whatsapp WHERE (to_id=" . $where['to_id'] . " AND from_id=" . $lead_id . ") AND is_client = 0 AND is_read='no' AND sms_direction='incoming'");
        } else {
            $query = $CI->db->query("SELECT count(*) as unread FROM " . db_prefix() . "lead_manager_whatsapp WHERE (to_id=" . $where['to_id'] . " AND from_id=" . $lead_id . ") AND is_client = 1 AND is_read='no' AND sms_direction='incoming'");
        }
        return $query->row()->unread;
    }
}
function get_filtered_whatsapp_sms($lead_id, $where)
{
    if (is_numeric($lead_id)) {
        $CI = &get_instance();
        $query = '';
        if ($where['is_client'] == 'no') {
            if ($where['is_read'] == 'all') {
                return true;
            } else {
                $query = $CI->db->query("SELECT count(*) as total FROM " . db_prefix() . "lead_manager_whatsapp WHERE to_id=" . $where['to_id'] . " AND from_id=" . $lead_id . " AND is_client = 0 AND is_read='" . $where['is_read'] . "' AND sms_direction='incoming'");
            }
        } else {
            if ($where['is_read'] == 'all') {
                return true;
            } else {
                $query = $CI->db->query("SELECT count(*) as total FROM " . db_prefix() . "lead_manager_whatsapp WHERE to_id=" . $where['to_id'] . " AND from_id=" . $lead_id . " AND is_client = 1 AND is_read='" . $where['is_read'] . "' AND sms_direction='incoming'");
            }
        }
        return $query->row()->total;
    }
}
function is_whats_app_enable($id, $type)
{
    $slug = $type . '_whatsapp_enable';
    if ($id) {
        $CI = &get_instance();
        $twilio_result = $CI->db->get_where(db_prefix() . 'customfields', ['slug' => $slug, 'fieldto' => $type])->row();
        if (isset($twilio_result) && !empty($twilio_result)) {
            $CI->db->select('value');
            $CI->db->where(['relid' => $id, 'fieldto' => $type, 'fieldid' => $twilio_result->id]);
            $res = $CI->db->get(db_prefix() . 'customfieldsvalues')->row();
            return ($res) && $res->value == 'Enable' ? true : false;
        } else {
            return false;
        }
    }
    return false;
}
function get_filtered_coversation_sms($lead_id, $where)
{
    if (is_numeric($lead_id)) {
        $CI = &get_instance();
        $query = '';
        if ($where['is_client'] == 'no') {
            if ($where['is_read'] == 'all') {
                // $query = $CI->db->query("SELECT count(*) as total FROM " . db_prefix() . "lead_manager_conversation WHERE to_id=" . $where['to_id'] . " AND from_id=" . $lead_id . " AND is_client = 0 AND sms_direction='incoming'");
                return true;
            } else {
                $query = $CI->db->query("SELECT count(*) as total FROM " . db_prefix() . "lead_manager_conversation WHERE to_id=" . $where['to_id'] . " AND from_id=" . $lead_id . " AND is_client = 0 AND is_read='" . $where['is_read'] . "' AND sms_direction='incoming'");
            }
        } else {
            if ($where['is_read'] == 'all') {
                // $query = $CI->db->query("SELECT count(*) as total FROM " . db_prefix() . "lead_manager_conversation WHERE to_id=" . $where['to_id'] . " AND from_id=" . $lead_id . " AND is_client = 1 AND sms_direction='incoming'");
                return true;
            } else {
                $query = $CI->db->query("SELECT count(*) as total FROM " . db_prefix() . "lead_manager_conversation WHERE to_id=" . $where['to_id'] . " AND from_id=" . $lead_id . " AND is_client = 1 AND is_read='" . $where['is_read'] . "' AND sms_direction='incoming'");
            }
        }
        return $query->row()->total;
    }
}
function handle_whatsapp_file_uploads($reciever_id, $sender_type)
{
    $staff_id = get_staff_user_id();
    $filesIDS = [];
    $errors   = [];

    if (
        isset($_FILES['file']['name'])
        && ($_FILES['file']['name'] != '' || is_array($_FILES['file']['name']) && count($_FILES['file']['name']) > 0)
    ) {

        if (!is_array($_FILES['file']['name'])) {
            $_FILES['file']['name']     = [$_FILES['file']['name']];
            $_FILES['file']['type']     = [$_FILES['file']['type']];
            $_FILES['file']['tmp_name'] = [$_FILES['file']['tmp_name']];
            $_FILES['file']['error']    = [$_FILES['file']['error']];
            $_FILES['file']['size']     = [$_FILES['file']['size']];
        }
        $path = LEAD_MANAGER_WHATSAPP_FOLDER . $sender_type . '/' . $staff_id . '/' . $reciever_id . '/';
        for ($i = 0; $i <= 2; $i++) {
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
                fopen($path . 'index.html', 'w');
                $fp = fopen($path . 'index.html', 'a+');
                if ($fp) {
                    fclose($fp);
                }
            }
        }
        for ($i = 0; $i < count($_FILES['file']['name']); $i++) {
            if (_perfex_upload_error($_FILES['file']['error'][$i])) {
                $errors[$_FILES['file']['name'][$i]] = _perfex_upload_error($_FILES['file']['error'][$i]);
                continue;
            }

            // Get the temp file path
            $tmpFilePath = $_FILES['file']['tmp_name'][$i];
            // Make sure we have a filepath
            if (!empty($tmpFilePath) && $tmpFilePath != '') {
                //die($path);
                _maybe_create_upload_path($path);
                $filename = unique_filename($path, $_FILES['file']['name'][$i]);

                // In case client side validation is bypassed
                if (!_upload_extension_allowed($filename)) {
                    continue;
                }

                $newFilePath = $path . $filename;
                // Upload the file into the company uploads dir
                if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                    $CI = &get_instance();
                    $data = [
                        'type' => $sender_type,
                        'file_name'  => $filename,
                        'filetype'   => $_FILES['file']['type'][$i],
                        'dateadded'  => date('Y-m-d H:i:s'),
                        'from_id'  => $staff_id,
                        'to_id'  => $reciever_id
                    ];
                    $CI->db->insert(db_prefix() . 'lead_manager_whatsapp_files', $data);
                    $insert_id = $CI->db->insert_id();
                    if ($insert_id) {
                        if (is_image($newFilePath)) {
                            create_img_thumb($path, $filename);
                        }
                        array_push($filesIDS, $insert_id);
                    } else {
                        unlink($newFilePath);
                        return false;
                    }
                }
            }
        }
    }

    if (count($errors) > 0) {
        $message = '';
        foreach ($errors as $filename => $error_message) {
            $message .= $filename . ' - ' . $error_message . '<br />';
        }
        header('HTTP/1.0 400 Bad error');
        echo $message;
        die;
    }

    if (count($filesIDS) > 0) {
        return sendWhatsappMultimedia($filesIDS, $reciever_id, $sender_type);
        //return json_encode($filesIDS);
    }

    return false;
}
function sendWhatsappMultimedia($filesIDS, $reciever_id, $sender_type)
{
    if (count($filesIDS) > 0) {
        $CI = &get_instance();
        $sender = '';
        $record = $CI->db->where_in('id', $filesIDS)->get(db_prefix() . 'lead_manager_whatsapp_files')->row();
        $twilio = null;
        if (isset($record) && !empty($record)) {
            if (get_option('call_twilio_active')) {
                $sid  = get_option('call_twilio_account_sid');
                $token  = get_option('call_twilio_auth_token');
                try {
                    $twilio = new Client($sid, $token);
                } catch (RestException $e) {
                    return json_encode(array('message_response' => $e->getMessage()));
                }
                if ($sender_type == 'client') {
                    $sender = $CI->clients_model->get_contact($reciever_id);
                    if (!isset($sender) && empty($sender)) {
                        $sender = $CI->clients_model->get($reciever_id);
                    }
                } else {
                    $sender = $CI->lead_manager_model->get($reciever_id);
                }
                $phoneNumber = $sender->phonenumber;
                $staff_twilio_number = get_staff_own_twilio_number();
                $message = '';
                try {
                    $message = $twilio->messages
                        ->create(
                            "whatsapp:" . $phoneNumber, // to
                            [
                                "from" => "whatsapp:" . $staff_twilio_number,
                                "mediaUrl" => [base_url('uploads/lead_manager/whatsapp/' . $record->type . '/' . $record->from_id . '/' . $record->to_id . '/' . $record->file_name)]
                            ]
                        );
                } catch (TwilioException $e) {
                    $file_path =  LEAD_MANAGER_WHATSAPP_FOLDER . $record->type . '/' . $record->from_id . '/' . $record->to_id . '/' . $record->file_name;
                    if (lm_delete_file($file_path)) {
                        $CI->db->where('id', $record->id);
                        $CI->db->delete(db_prefix() . 'lead_manager_whatsapp_files');
                    }
                    return json_encode(array('message_response' => $e->getMessage()));
                }
                if (!empty($message)) {
                    $msg_response['accountSid'] = $message->accountSid;
                    $msg_response['apiVersion'] = $message->apiVersion;
                    $msg_response['body'] = $message->body;
                    $msg_response['dateCreated'] = $message->dateCreated;
                    $msg_response['dateUpdated'] = $message->dateUpdated;
                    $msg_response['dateSent'] = $message->dateSent;
                    $msg_response['direction'] = $message->direction;
                    $msg_response['from'] = $message->from;
                    $msg_response['messagingServiceSid'] = $message->messagingServiceSid;
                    $msg_response['numMedia'] = $message->numMedia;
                    $msg_response['numSegments'] = $message->numSegments;
                    $msg_response['sid'] = $message->sid;
                    $msg_response['status'] = $message->status;
                    $msg_response['to'] = $message->to;
                    $response['success'] = true;
                    $data['type'] = 'whatsapp';
                    $data['lead_id'] = $reciever_id;
                    $data['date'] = date("Y-m-d H:i:s");
                    $data['additional_data'] = base_url('uploads/lead_manager/whatsapp/' . $record->type . '/' . $record->from_id . '/' . $record->to_id . '/' . $record->file_name);
                    $data['staff_id'] = $sender_type == 'client' ? get_staff_user_id() : $sender->assigned;
                    $data['direction'] = 'outgoing';
                    $data['is_client'] = $sender_type == 'client' ? 1 : 0;
                    $response_activity = $CI->lead_manager_model->lead_manger_activity_log($data);
                    if ($sender_type != 'client') {
                        $CI->lead_manager_model->update_last_contact($reciever_id);
                        $response['profile_image'] = base_url('assets/images/user-placeholder.jpg');
                    } else {
                        $primary_contact_id = get_primary_contact_user_id($reciever_id);
                        if (isset($primary_contact_id) && !empty($primary_contact_id)) {
                            $response['profile_image'] = contact_profile_image_url($primary_contact_id);
                        }
                    }
                    $data['media'] = $record->id;
                    $response['sms_id'] = $CI->lead_manager_model->create_conversation_whatsaap($msg_response, $data);
                    $response['time'] = _dt(date("Y-m-d H:i:s"));
                    $response['sms_status'] = $msg_response['status'];
                    return json_encode(array('message_response' => $msg_response, 'media_record' => $record, 'response' => $response));
                }
            }
        }
    }
}
function fetchTwilioMedia($msg_id, $meadia_id)
{
    $data = array('content_type' => null, 'uri' => null);
    $sid  = get_option('call_twilio_account_sid');
    $token  = get_option('call_twilio_auth_token');
    $twilio = null;
    try {
        $twilio = new Client($sid, $token);
    } catch (RestException $e) {
        log_activity('Unable to connect Twilio account! ' . $e->getMessage());
        return $data;
    }
    $media = $twilio->messages($msg_id)->media($meadia_id)->fetch();
    if (isset($media) && !empty($media)) {
        $data['content_type'] = $media->content_type;
        $data['uri'] = $media->uri;
    }
    return $data;
}
function mime2ext($mime)
{
    $mime_map = [
        'video/3gpp2'                                                               => '3g2',
        'video/3gp'                                                                 => '3gp',
        'video/3gpp'                                                                => '3gp',
        'application/x-compressed'                                                  => '7zip',
        'audio/x-acc'                                                               => 'aac',
        'audio/ac3'                                                                 => 'ac3',
        'application/postscript'                                                    => 'ai',
        'audio/x-aiff'                                                              => 'aif',
        'audio/aiff'                                                                => 'aif',
        'audio/x-au'                                                                => 'au',
        'video/x-msvideo'                                                           => 'avi',
        'video/msvideo'                                                             => 'avi',
        'video/avi'                                                                 => 'avi',
        'application/x-troff-msvideo'                                               => 'avi',
        'application/macbinary'                                                     => 'bin',
        'application/mac-binary'                                                    => 'bin',
        'application/x-binary'                                                      => 'bin',
        'application/x-macbinary'                                                   => 'bin',
        'image/bmp'                                                                 => 'bmp',
        'image/x-bmp'                                                               => 'bmp',
        'image/x-bitmap'                                                            => 'bmp',
        'image/x-xbitmap'                                                           => 'bmp',
        'image/x-win-bitmap'                                                        => 'bmp',
        'image/x-windows-bmp'                                                       => 'bmp',
        'image/ms-bmp'                                                              => 'bmp',
        'image/x-ms-bmp'                                                            => 'bmp',
        'application/bmp'                                                           => 'bmp',
        'application/x-bmp'                                                         => 'bmp',
        'application/x-win-bitmap'                                                  => 'bmp',
        'application/cdr'                                                           => 'cdr',
        'application/coreldraw'                                                     => 'cdr',
        'application/x-cdr'                                                         => 'cdr',
        'application/x-coreldraw'                                                   => 'cdr',
        'image/cdr'                                                                 => 'cdr',
        'image/x-cdr'                                                               => 'cdr',
        'zz-application/zz-winassoc-cdr'                                            => 'cdr',
        'application/mac-compactpro'                                                => 'cpt',
        'application/pkix-crl'                                                      => 'crl',
        'application/pkcs-crl'                                                      => 'crl',
        'application/x-x509-ca-cert'                                                => 'crt',
        'application/pkix-cert'                                                     => 'crt',
        'text/css'                                                                  => 'css',
        'text/x-comma-separated-values'                                             => 'csv',
        'text/comma-separated-values'                                               => 'csv',
        'application/vnd.msexcel'                                                   => 'csv',
        'application/x-director'                                                    => 'dcr',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
        'application/x-dvi'                                                         => 'dvi',
        'message/rfc822'                                                            => 'eml',
        'application/x-msdownload'                                                  => 'exe',
        'video/x-f4v'                                                               => 'f4v',
        'audio/x-flac'                                                              => 'flac',
        'video/x-flv'                                                               => 'flv',
        'image/gif'                                                                 => 'gif',
        'application/gpg-keys'                                                      => 'gpg',
        'application/x-gtar'                                                        => 'gtar',
        'application/x-gzip'                                                        => 'gzip',
        'application/mac-binhex40'                                                  => 'hqx',
        'application/mac-binhex'                                                    => 'hqx',
        'application/x-binhex40'                                                    => 'hqx',
        'application/x-mac-binhex40'                                                => 'hqx',
        'text/html'                                                                 => 'html',
        'image/x-icon'                                                              => 'ico',
        'image/x-ico'                                                               => 'ico',
        'image/vnd.microsoft.icon'                                                  => 'ico',
        'text/calendar'                                                             => 'ics',
        'application/java-archive'                                                  => 'jar',
        'application/x-java-application'                                            => 'jar',
        'application/x-jar'                                                         => 'jar',
        'image/jp2'                                                                 => 'jp2',
        'video/mj2'                                                                 => 'jp2',
        'image/jpx'                                                                 => 'jp2',
        'image/jpm'                                                                 => 'jp2',
        'image/jpeg'                                                                => 'jpeg',
        'image/pjpeg'                                                               => 'jpeg',
        'application/x-javascript'                                                  => 'js',
        'application/json'                                                          => 'json',
        'text/json'                                                                 => 'json',
        'application/vnd.google-earth.kml+xml'                                      => 'kml',
        'application/vnd.google-earth.kmz'                                          => 'kmz',
        'text/x-log'                                                                => 'log',
        'audio/x-m4a'                                                               => 'm4a',
        'audio/mp4'                                                                 => 'm4a',
        'application/vnd.mpegurl'                                                   => 'm4u',
        'audio/midi'                                                                => 'mid',
        'application/vnd.mif'                                                       => 'mif',
        'video/quicktime'                                                           => 'mov',
        'video/x-sgi-movie'                                                         => 'movie',
        'audio/mpeg'                                                                => 'mp3',
        'audio/mpg'                                                                 => 'mp3',
        'audio/mpeg3'                                                               => 'mp3',
        'audio/mp3'                                                                 => 'mp3',
        'video/mp4'                                                                 => 'mp4',
        'video/mpeg'                                                                => 'mpeg',
        'application/oda'                                                           => 'oda',
        'audio/ogg'                                                                 => 'ogg',
        'video/ogg'                                                                 => 'ogg',
        'application/ogg'                                                           => 'ogg',
        'font/otf'                                                                  => 'otf',
        'application/x-pkcs10'                                                      => 'p10',
        'application/pkcs10'                                                        => 'p10',
        'application/x-pkcs12'                                                      => 'p12',
        'application/x-pkcs7-signature'                                             => 'p7a',
        'application/pkcs7-mime'                                                    => 'p7c',
        'application/x-pkcs7-mime'                                                  => 'p7c',
        'application/x-pkcs7-certreqresp'                                           => 'p7r',
        'application/pkcs7-signature'                                               => 'p7s',
        'application/pdf'                                                           => 'pdf',
        'application/octet-stream'                                                  => 'pdf',
        'application/x-x509-user-cert'                                              => 'pem',
        'application/x-pem-file'                                                    => 'pem',
        'application/pgp'                                                           => 'pgp',
        'application/x-httpd-php'                                                   => 'php',
        'application/php'                                                           => 'php',
        'application/x-php'                                                         => 'php',
        'text/php'                                                                  => 'php',
        'text/x-php'                                                                => 'php',
        'application/x-httpd-php-source'                                            => 'php',
        'image/png'                                                                 => 'png',
        'image/x-png'                                                               => 'png',
        'application/powerpoint'                                                    => 'ppt',
        'application/vnd.ms-powerpoint'                                             => 'ppt',
        'application/vnd.ms-office'                                                 => 'ppt',
        'application/msword'                                                        => 'doc',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/x-photoshop'                                                   => 'psd',
        'image/vnd.adobe.photoshop'                                                 => 'psd',
        'audio/x-realaudio'                                                         => 'ra',
        'audio/x-pn-realaudio'                                                      => 'ram',
        'application/x-rar'                                                         => 'rar',
        'application/rar'                                                           => 'rar',
        'application/x-rar-compressed'                                              => 'rar',
        'audio/x-pn-realaudio-plugin'                                               => 'rpm',
        'application/x-pkcs7'                                                       => 'rsa',
        'text/rtf'                                                                  => 'rtf',
        'text/richtext'                                                             => 'rtx',
        'video/vnd.rn-realvideo'                                                    => 'rv',
        'application/x-stuffit'                                                     => 'sit',
        'application/smil'                                                          => 'smil',
        'text/srt'                                                                  => 'srt',
        'image/svg+xml'                                                             => 'svg',
        'application/x-shockwave-flash'                                             => 'swf',
        'application/x-tar'                                                         => 'tar',
        'application/x-gzip-compressed'                                             => 'tgz',
        'image/tiff'                                                                => 'tiff',
        'font/ttf'                                                                  => 'ttf',
        'text/plain'                                                                => 'txt',
        'text/x-vcard'                                                              => 'vcf',
        'application/videolan'                                                      => 'vlc',
        'text/vtt'                                                                  => 'vtt',
        'audio/x-wav'                                                               => 'wav',
        'audio/wave'                                                                => 'wav',
        'audio/wav'                                                                 => 'wav',
        'application/wbxml'                                                         => 'wbxml',
        'video/webm'                                                                => 'webm',
        'image/webp'                                                                => 'webp',
        'audio/x-ms-wma'                                                            => 'wma',
        'application/wmlc'                                                          => 'wmlc',
        'video/x-ms-wmv'                                                            => 'wmv',
        'video/x-ms-asf'                                                            => 'wmv',
        'font/woff'                                                                 => 'woff',
        'font/woff2'                                                                => 'woff2',
        'application/xhtml+xml'                                                     => 'xhtml',
        'application/excel'                                                         => 'xl',
        'application/msexcel'                                                       => 'xls',
        'application/x-msexcel'                                                     => 'xls',
        'application/x-ms-excel'                                                    => 'xls',
        'application/x-excel'                                                       => 'xls',
        'application/x-dos_ms_excel'                                                => 'xls',
        'application/xls'                                                           => 'xls',
        'application/x-xls'                                                         => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
        'application/vnd.ms-excel'                                                  => 'xlsx',
        'application/xml'                                                           => 'xml',
        'text/xml'                                                                  => 'xml',
        'text/xsl'                                                                  => 'xsl',
        'application/xspf+xml'                                                      => 'xspf',
        'application/x-compress'                                                    => 'z',
        'application/x-zip'                                                         => 'zip',
        'application/zip'                                                           => 'zip',
        'application/x-zip-compressed'                                              => 'zip',
        'application/s-compressed'                                                  => 'zip',
        'multipart/x-zip'                                                           => 'zip',
        'text/x-scriptzsh'                                                          => 'zsh',
    ];

    return isset($mime_map[$mime]) ? $mime_map[$mime] : false;
}
function getTwilioMediaContent($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $media = curl_exec($ch);
    curl_close($ch);
    return $media;
}
function get_answered_coversation_sms($lead_id, $where)
{
    if (is_numeric($lead_id)) {
        $CI = &get_instance();
        $query = '';
        if ($where['is_client'] == 'no') {
            if ($where['is_answered'] == 'all') {
                return true;
            } else if ($where['is_answered'] == 'yes') {
                $query = $CI->db->query("SELECT MAX(id) as 
                total FROM " . db_prefix() . "lead_manager_conversation WHERE to_id=" . $where['to_id'] . " AND from_id=" . $lead_id . " AND is_client = 0 AND sms_direction='incoming'");
            } else if ($where['is_answered'] == 'no') {
                $query = $CI->db->query("SELECT MAX(id) as total FROM " . db_prefix() . "lead_manager_conversation WHERE to_id=" . $lead_id . " AND from_id=" . $where['to_id'] . " AND is_client = 0 AND sms_direction='outgoing'");
                // $query = $CI->db->query("SELECT id, sms_direction FROM " . db_prefix() . "lead_manager_conversation WHERE (to_id=" . $lead_id . " AND from_id=" . $where['to_id'] . " OR to_id=" . $where['to_id'] . " AND from_id=" . $lead_id . ") AND is_client = 0 ORDER BY id DESC LIMIT 1");
                // return isset($query->row()->sms_direction) && $query->row()->sms_direction == 'incoming' ? 1 : 0;
            }
        } else {
            if ($where['is_answered'] == 'all') {
                return true;
            } else {
                $query = $CI->db->query("SELECT count(*) as total FROM " . db_prefix() . "lead_manager_conversation WHERE to_id=" . $where['to_id'] . " AND from_id=" . $lead_id . " AND is_client = 1 AND is_read='" . $where['is_answered'] . "' AND sms_direction='incoming'");
            }
        }
        return $query->row()->total;
    }
}
function render_lead_manager_templates()
{
    $CI = &get_instance();
    if (!has_permission('email_templates', '', 'view')) {
        access_denied('email_templates');
    }
    $langCheckings = get_option('email_templates_language_checks');
    if ($langCheckings == '') {
        $langCheckings = [];
    } else {
        $langCheckings = unserialize($langCheckings);
    }
    // $CI->db->where('language', 'english');
    $CI->db->where("`language`='english' AND `type` LIKE 'lead_manager_%'");
    $email_templates_english = $CI->db->get(db_prefix() . 'emailtemplates')->result_array();
    foreach ($CI->app->get_available_languages() as $avLanguage) {
        if ($avLanguage != 'english') {
            foreach ($email_templates_english as $template) {
                if (isset($langCheckings[$template['slug'] . '-' . $avLanguage])) {
                    continue;
                }

                $notExists = total_rows(db_prefix() . 'emailtemplates', [
                    'slug'     => $template['slug'],
                    'language' => $avLanguage,
                ]) == 0;

                $langCheckings[$template['slug'] . '-' . $avLanguage] = 1;

                if ($notExists) {
                    $data              = [];
                    $data['slug']      = $template['slug'];
                    $data['type']      = $template['type'];
                    $data['language']  = $avLanguage;
                    $data['name']      = $template['name'] . ' [' . $avLanguage . ']';
                    $data['subject']   = $template['subject'];
                    $data['message']   = '';
                    $data['fromname']  = $template['fromname'];
                    $data['plaintext'] = $template['plaintext'];
                    $data['active']    = $template['active'];
                    $data['order']     = $template['order'];
                    $CI->db->insert(db_prefix() . 'emailtemplates', $data);
                }
            }
        }
    }
    update_option('email_templates_language_checks', serialize($langCheckings));
    $data['templates'] = $CI->emails_model->get("`language`='english' AND `type` LIKE 'lead_manager_%'");
    $data['title'] = _l('email_templates');
    $data['hasPermissionEdit'] = has_permission('email_templates', '', 'edit');
    echo $CI->load->view('lead_manager/admin/email_templates', $data);
}
function getTruncatedPhoneNumber($ccNum)
{
    return str_replace(range(0, 9), "*", substr($ccNum, 0, -4)) .  substr($ccNum, -4);
}
function leads_table_row_infected($row, $aRow)
{
    if (!has_permission('lead_manager', '', 'show_contact')) {
        $aRowIndex = array_search('phonenumber', array_keys($aRow));
        if (isset($aRow['phonenumber']) && !empty($aRow['phonenumber'])) {
            $row[$aRowIndex] = getTruncatedPhoneNumber($aRow['phonenumber']);
        }
    }
    return $row;
}
function leads_profile_data_infected($lead_data)
{
    if (!has_permission('lead_manager', '', 'show_contact')) {
        $lead_data['lead']->phonenumber = getTruncatedPhoneNumber($lead_data['lead']->phonenumber);
    }
    return $lead_data;
}
function isValidTimeStamp($timestamp)
{
    return ((string) (int) $timestamp === $timestamp)
        && ($timestamp <= PHP_INT_MAX)
        && ($timestamp >= ~PHP_INT_MAX);
}
function lm_delete_file($path)
{
    if (file_exists($path)) {
        unlink($path);
    }
}
function handle_db_after_installation()
{
    $CI = &get_instance();
    if ($CI->db->field_exists('template_id', db_prefix() . 'lead_manager_whatsapp_templates')) {
        $fields = $CI->db->field_data('lead_manager_whatsapp_templates');
        foreach ($fields as $field) {
            if ($field->name == 'template_id' && $field->type == 'bigint') {
                $CI->db->query("ALTER TABLE `" . db_prefix() . "lead_manager_whatsapp_templates` CHANGE `template_id` `template_id` varchar(255) NULL DEFAULT NULL");
            }
        }
    }
}