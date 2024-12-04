<?php defined('BASEPATH') or exit('No direct script access allowed');
$check =  __dir__;
$str = preg_replace('/\W\w+\s*(\W*)$/', '$1', $check);
$str . '/third_party/twilio-web/src/Twilio/autoload.php';

use Twilio\TwiML\VoiceResponse;
use Twilio\Rest\Client;

class Call_control extends CI_Controller
{
    var $call_direction;
    public function __construct()
    {
        parent::__construct();
        // hooks()->do_action('after_clients_area_init', $this);
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

    public function handleCall()
    {
        if (empty($_GET)) {
            access_denied();
        }
        $this->load->helper('lead_manager');
        $response = new VoiceResponse();
        $callerIdNumber = isset($_GET['callerIdNumber']) ? $_GET['callerIdNumber'] : get_option('call_twilio_phone_number');
        $leadid = (isset($_GET['leadId'])) ? $_GET['leadId'] : null;
        $is_lead = (isset($_GET['is_lead'])) ? $_GET['is_lead'] : null;
        $client_id = (isset($_GET['client_id'])) ? $_GET['client_id'] : null;
        $phoneNumberToDial = isset($_GET['phoneNumber']) ? $_GET['phoneNumber'] : null;
        if (isset($callerIdNumber)) {
            if (get_option('call_twilio_recording_active')) {
                $dial = $response->dial('', [
                    'callerId' => $callerIdNumber, 'record' => 'record-from-ringing-dual',
                    'recordingStatusCallback' => base_url() . 'lead_manager/Call_control/recordCall/' . $leadid . '?is_lead=' . $is_lead . '&client_id=' . $client_id, 'recordingStatusCallbackMethod' => 'GET', 'recordingTrack' => 'both'
                ]);
                if (isset($phoneNumberToDial)) {
                    $call_direction = 'outgoing';
                    $dial->number($phoneNumberToDial);
                } else {
                    $staff_id = get_staff_by_twilio_number($_GET['To']);
                    $call_direction = 'incoming';
                    $dial->client('support_agent_'.$staff_id);
                }
                $response->play('https://api.twilio.com/cowbell.mp3');
                header('Content-Type: text/xml');
                echo $response;
            } else {
                $dial = $response->dial('', ['callerId' => $callerIdNumber]);
                if (isset($phoneNumberToDial)) {
                    $call_direction = 'outgoing';
                    $dial->number($phoneNumberToDial, ['statusCallback' => base_url() . 'lead_manager/Call_control/handleNotRecordedOutgoingCall/' . $leadid . '?call_direction=' . $call_direction . '&is_lead=' . $is_lead . '&client_id=' . $client_id, 'statusCallbackMethod' => 'GET']);
                } else {
                    $staff_id = get_staff_by_twilio_number($_GET['To']);
                    $call_direction = 'incoming';
                    $dial->client('support_agent_'.$staff_id, ['statusCallback' => base_url() . 'lead_manager/Call_control/handleNotRecordedIncomingCall/' . $leadid . '?call_direction=' . $call_direction . '&is_lead=' . $is_lead . '&client_id=' . $client_id, 'statusCallbackMethod' => 'GET']);
                }
                $response->play('https://api.twilio.com/cowbell.mp3');
                header('Content-Type: text/xml');
                echo $response;
            }
        }
    }

    public function handleNotRecordedIncomingCall($lead_id = null)
    {
        $durationData = '';
        $call = '';
        $is_lead = $this->input->get('is_lead');
        $call_direction = $this->input->get('call_direction');
        $status = $this->input->get('CallStatus');
        if (!$lead_id) {
            $pSid = $_GET['ParentCallSid'];
            $sid  = get_option('call_twilio_account_sid');
            $token  = get_option('call_twilio_auth_token');
            $twilio = new Client($sid, $token);
            $call = $twilio->calls($pSid)->fetch();
            $lead_id = $this->get_lead_id_by_number($call->from);
            if (!$lead_id) {
                $lead = $this->lead_manager_model->get_contact('', ['is_primary' => 1, 'phonenumber' => $call->from]);
                if (!isset($lead) && empty($lead)) {
                    $lead = $this->lead_manager_model->get_client('', ['active' => 1, 'phonenumber' => $call->from]);
                    $lead_id = $lead->user_id;
                }
                $lead_id = $lead->user_id;
            }
        }
        if ($lead_id) {
            if (isset($call) && !empty($call)) {
                $durationData .= '<div class="task-info task-info-billable"><h5><i class="fa fa-volume-control-phone" aria-hidden="true"></i> <b>Call Start Time</b> : ' . $call->startTime->format('Y-m-d H:i:s') . '</h5></div>';
                $durationData .= '<div class="task-info task-info-billable"><h5><i class="fa fa-tty" aria-hidden="true"></i> <b>Call End Time</b> : ' . $call->endTime->format('Y-m-d H:i:s') . '</h5></div>';
                $durationData .= '<div class="task-info task-info-billable"><h5><i class="fa fa-info-circle" aria-hidden="true"></i> <b>Call Duration</b> : ' . gmdate("H:i:s", $call->duration) . ' SEC</h5></div>';
            } else {
                $durationData = '<div class="task-info task-info-billable"><h5><i class="fa fa-info-circle" aria-hidden="true"></i>' . _l('lead_manger_call_duration') . ': ' . gmdate("H:i:s", $this->input->get('CallDuration')) . ' SEC</h5></div>';
            }
            $this->load->model('lead_manager_model');
            $lead = $this->lead_manager_model->get($lead_id);
            if ($is_lead) {
                $last_contact = $this->lead_manager_model->update_last_contact($lead_id);
            }
            $data['type'] = 'audio_call';
            $data['lead_id'] = $lead_id;
            $data['date'] = date("Y-m-d H:i:s");
            $data['description'] = $durationData;
            $data['additional_data'] = json_encode($this->input->get());
            $data['staff_id'] = !$is_lead ? $lead->assigned : 0;
            $data['direction'] = $call_direction;
            $data['call_duration'] = $call->duration;
            $data['is_client'] = !$is_lead ? 1 : 0;
            $response = $this->lead_manager_model->lead_manger_activity_log($data);
        }
    }
    public function handleNotRecordedOutgoingCall($lead_id = null)
    {
        $durationData = '';
        $call = '';
        $call_direction = $this->input->get('call_direction');
        $status = $this->input->get('CallStatus');
        $is_lead = $this->input->get('is_lead');
        if ($lead_id) {
            $pSid = $_GET['CallSid'];
            $sid  = get_option('call_twilio_account_sid');
            $token  = get_option('call_twilio_auth_token');
            $twilio = new Client($sid, $token);
            $call = $twilio->calls($pSid)->fetch();
            if (isset($call) && !empty($call)) {
                $durationData .= '<div class="task-info task-info-billable"><h5><i class="fa fa-volume-control-phone" aria-hidden="true"></i> <b>Call Start Time</b> : ' . $call->startTime->format('Y-m-d H:i:s') . '</h5></div>';
                $durationData .= '<div class="task-info task-info-billable"><h5><i class="fa fa-tty" aria-hidden="true"></i> <b>Call End Time</b> : ' . $call->endTime->format('Y-m-d H:i:s') . '</h5></div>';
                $durationData .= '<div class="task-info task-info-billable"><h5><i class="fa fa-info-circle" aria-hidden="true"></i> <b>Call Duration</b> : ' . gmdate("H:i:s", $call->duration) . ' SEC</h5></div>';
            } else {
                $durationData = '<div class="task-info task-info-billable"><h5><i class="fa fa-info-circle" aria-hidden="true"></i> <b>Call Duration</b> : ' . gmdate("H:i:s", $this->input->get('CallDuration')) . ' SEC</h5></div>';
            }
            $this->load->model('lead_manager_model');
            $data['type'] = 'audio_call';
            $data['date'] = date("Y-m-d H:i:s");
            $data['description'] = $durationData;
            $data['additional_data'] = json_encode($this->input->get());
            $data['direction'] = $call_direction;
            $data['call_duration'] = $call->duration;
            $data['is_client'] = !$is_lead ? 1 : 0;
            if ($is_lead) {
                $lead = $this->lead_manager_model->get($lead_id);
                $last_contact = $this->lead_manager_model->update_last_contact($lead_id);
                $data['lead_id'] = $lead_id;
                $data['staff_id'] = $lead->assigned;
            } else {
                $data['lead_id'] = $this->input->get('client_id');
                $data['staff_id'] = 0;
            }
            $response = $this->lead_manager_model->lead_manger_activity_log($data);
        }
    }

    public function get_lead_id_by_number($number)
    {
        if ($number) {
            $CI = &get_instance();
            $CI->db->select('id');
            $CI->db->where(['phonenumber' => $number, 'client_id' => 0]);
            $res = $CI->db->get(db_prefix() . 'leads')->row();
            return ($res) ? $res->id : false;
        }
        return false;
    }

    public function recordCall($lead_id = NULL)
    {
        $this->load->model('lead_manager_model');
        $is_lead = 0;
        $durationData = NULL;
        $call_direction = NULL;
        $data = array();
        $client_id = $this->input->get('client_id');
        if ($lead_id) {
            $pSid = $_GET['CallSid'];
            $sid  = get_option('call_twilio_account_sid');
            $token  = get_option('call_twilio_auth_token');
            $twilio = new Client($sid, $token);
            $call = $twilio->calls($pSid)->fetch();
            $call_direction = 'outgoing';
            $is_lead = $_GET['is_lead'];
        } else {
            $pSid = $_GET['CallSid'];
            $sid  = get_option('call_twilio_account_sid');
            $token  = get_option('call_twilio_auth_token');
            $twilio = new Client($sid, $token);
            $call = $twilio->calls($pSid)->fetch();
            $lead_id = $this->get_lead_id_by_number($call->from);
            if (!$lead_id) {
                $lead = $this->lead_manager_model->get_contact('', ['is_primary' => 1, 'phonenumber' => $call->from]);
                if (!isset($lead) && empty($lead)) {
                    $lead = $this->lead_manager_model->get_client('', ['active' => 1, 'phonenumber' => $call->from]);
                    $lead_id = $lead->user_id;
                }
                $lead_id = $lead->user_id;
            }
            $call_direction = 'incoming';
        }
        if (isset($call) && !empty($call)) {
            $durationData .= '<div class="task-info task-info-billable"><h5><i class="fa fa-volume-control-phone" aria-hidden="true"></i> <b>Call Start Time</b> : ' . $call->startTime->format('Y-m-d H:i:s') . '</h5></div>';
            $durationData .= '<div class="task-info task-info-billable"><h5><i class="fa fa-tty" aria-hidden="true"></i> <b>Call End Time</b> : ' . $call->endTime->format('Y-m-d H:i:s') . '</h5></div>';
            $durationData .= '<div class="task-info task-info-billable"><h5><i class="fa fa-info-circle" aria-hidden="true"></i> <b>Call Duration</b> : ' . gmdate("H:i:s", $call->duration) . ' SEC</h5></div>';
        } else {
            $durationData = '<div class="task-info task-info-billable"><h5><i class="fa fa-info-circle" aria-hidden="true"></i>' . _l('lead_manger_call_duration') . ': ' . gmdate("H:i:s", $this->input->get('CallDuration')) . ' SEC</h5></div>';
        }
        if ($is_lead) {
            $lead = $this->lead_manager_model->get($lead_id);
            $last_contact = $this->lead_manager_model->update_last_contact($lead_id);
        }
        $data['type'] = 'audio_call';
        $data['is_audio_call_recorded'] = TRUE;
        $data['lead_id'] = !$is_lead ? $client_id : $lead_id;
        $data['date'] = date("Y-m-d H:i:s");
        $data['description'] = $durationData;
        $data['additional_data'] = json_encode($this->input->get());
        $data['staff_id'] = !$is_lead ? 0 : $lead->assigned;
        $data['direction'] = $call_direction;
        $data['call_duration'] = $this->input->get('RecordingDuration');
        $data['is_client'] = !$is_lead ? 1 : 0;
        $response = $this->lead_manager_model->lead_manger_activity_log($data);
        echo $response;
    }
    public function getCallDetials($get)
    {
        $sid = $get['AccountSid'];
        $token = get_option('call_twilio_auth_token');
        $callSid = $get['CallSid'];
        $twilio = new Client($sid, $token);
        $call = $twilio->calls($callSid)->fetch();
        return $call->to;
    }
    public function getCallDetialsByCallSid($callSid)
    {
        $sid = 'AC1e491928aec1ddac82f49d5bbf13f616';
        $token = get_option('call_twilio_auth_token');
        $twilio = new Client($sid, $token);
        $call = $twilio->calls($callSid)->fetch();
        print_r($call);
    }

    public function holdCall()
    {
        $client = new Client(get_option('call_twilio_account_sid'), get_option('call_twilio_auth_token'));
        $calls = $client->calls->read(
            array("ParentCallSid" => $_POST['CallSid'])
        );
        if (!isset($calls) && empty($calls)) {
            $childCall = $client->calls($_POST['CallSid'])->fetch();
            $parentCallSid = $childCall->parentCallSid;
            $twilioCall = '';
            header('Content-Type: text/xml');
            $twilioCall = $client->calls($_POST['CallSid'])->update(
                array(
                    "url" => base_url() . 'lead_manager/Call_control/waitUrl',
                    "method" => "GET"
                )
            );
            $resp = $client->calls($parentCallSid)->update(
                array(
                    "url" => base_url() . 'lead_manager/Call_control/holdQueue',
                    "method" => "GET"
                )
            );
            echo $resp;
            exit;
        }
        $twilioCall = '';
        header('Content-Type: text/xml');
        $resp = $client->calls($_POST['CallSid'])->update(
            array(
                "url" => base_url() . 'lead_manager/Call_control/waitUrl',
                "method" => "GET"
            )
        );
        $twilioCall = $client->calls($calls[0]->sid)->update(
            array(
                "url" => base_url() . 'lead_manager/Call_control/holdQueue',
                "method" => "GET"
            )
        );
        echo $twilioCall;
    }
    public function holdQueue()
    {
        $to_number = $this->input->get('To');
        $response = new VoiceResponse();
        $response->enqueue('caller_on_hold', ['waitUrl' => base_url('modules/lead_manager/assets/wait-music.xml'), 'waitUrlMethod' => 'GET', 'method' => 'GET']);
        header('Content-Type: text/xml');
        echo $response;
    }
    public function waitUrl()
    {
        $response = new VoiceResponse();
        $response->play('https://api.twilio.com/cowbell.mp3', ['loop' => 100]);
        header('Content-Type: text/xml');
        echo $response;
    }
    public function unholdCall()
    {
        $client = new Client(get_option('call_twilio_account_sid'), get_option('call_twilio_auth_token'));
        header('Content-Type: text/xml');
        $client->calls($_POST['CallSid'])->update(
            array(
                "url" => admin_url() . 'lead_manager/Call_control/unholdQueue',
                "method" => "GET"
            )
        );
        echo 'done';
    }

    public function unholdQueue()
    {
        $to_number = $this->input->get('To');
        $response = new VoiceResponse();
        $dial = $response->dial('');
        $dial->queue('caller_on_hold');
        header('Content-Type: text/xml');
        echo $response;
    }

    public function confrenceCall()
    {
        $client = new Client(get_option('call_twilio_account_sid'), get_option('call_twilio_auth_token'));
        $resp_data = [];
        $calls = $client->calls->read(
            array("ParentCallSid" => $_POST['CallSid'])
        );
        $lead = null;
        $activity_data['is_client'] = 0;
        $activity_data['type'] = 'conference_call';
        if (isset($calls) && !empty($calls)) {
            //outgoing call conference
            $activity_data['lead_id'] = $this->get_lead_id_by_number($calls[0]->to);
            if (isset($activity_data['lead_id']) && !empty($activity_data['lead_id'])) {
                $lead = $this->lead_manager_model->get($activity_data['lead_id']);
            } else {
                $lead = $this->lead_manager_model->get_contact('', ['is_primary' => 1, 'phonenumber' => $calls[0]->to]);
                if (!isset($lead) && empty($lead)) {
                    $lead = $this->lead_manager_model->get_client('', ['active' => 1, 'phonenumber' => $calls[0]->to]);
                    if (isset($lead) && !empty($lead)) {
                        $activity_data['lead_id'] = $lead->user_id;
                    }
                }
                if (isset($lead) && !empty($lead)) {
                    $activity_data['lead_id'] = $lead->user_id;
                }
                $activity_data['is_client'] = 1;
            }
            $activity_data['direction'] = 'outgoing';
            $client->calls($calls[0]->sid)->update(
                array(
                    'twiml' => '<Response><Dial><Conference startConferenceOnEnter="False" endConferenceOnExit="False" statusCallback="' . base_url('lead_manager/Call_control/connfrenceCallBack/outgoing') . '" statusCallbackMethod="GET" statusCallbackEvent="start end join leave mute hold">' . $_POST['CallSid'] . '</Conference></Dial></Response>'
                )
            );
            $client->calls($_POST['CallSid'])->update(
                array(
                    'twiml' => '<Response><Dial><Conference startConferenceOnEnter="True" endConferenceOnExit="True" statusCallback="' . base_url('lead_manager/Call_control/connfrenceCallBack/outgoing') . '" statusCallbackMethod="GET" statusCallbackEvent="start end join leave mute hold">' . $_POST['CallSid'] . '</Conference></Dial></Response>'
                )
            );
            $resp_data['callSid'] = $calls[0]->sid;
            $resp_data['number'] = $calls[0]->to;
        } else {
            $calls = $client->calls($_POST['CallSid'])
                ->fetch();
            if (isset($calls) && !empty($calls)) {
                $pCall = $client->calls($calls->parentCallSid)
                    ->fetch();
                //incoming call conference
                $activity_data['lead_id'] = $this->get_lead_id_by_number($pCall->from);
                if (isset($activity_data['lead_id']) && !empty($activity_data['lead_id'])) {
                    $lead = $this->lead_manager_model->get($activity_data['lead_id']);
                } else {
                    $lead = $this->lead_manager_model->get_contact('', ['is_primary' => 1, 'phonenumber' => $calls->from]);
                    if (!isset($lead) && empty($lead)) {
                        $lead = $this->lead_manager_model->get_client('', ['active' => 1, 'phonenumber' => $calls->from]);
                        if (isset($lead) && !empty($lead)) {
                            $activity_data['lead_id'] = $lead->user_id;
                        }
                    }
                    if (isset($lead) && !empty($lead)) {
                        $activity_data['lead_id'] = $lead->user_id;
                    }
                    $activity_data['is_client'] = 1;
                }
                $activity_data['direction'] = 'incoming';
                $client->calls($_POST['CallSid'])->update(
                    array(
                        'twiml' => '<Response><Dial><Conference startConferenceOnEnter="True" endConferenceOnExit="True" statusCallback="' . base_url('lead_manager/Call_control/connfrenceCallBack/incoming') . '" statusCallbackMethod="GET" statusCallbackEvent="start end join leave mute hold">' . $_POST['CallSid'] . '</Conference></Dial></Response>'
                    )
                );
                $client->calls($calls->parentCallSid)->update(
                    array(
                        'twiml' => '<Response><Dial><Conference startConferenceOnEnter="False" endConferenceOnExit="False" statusCallback="' . base_url('lead_manager/Call_control/connfrenceCallBack/incoming') . '" statusCallbackMethod="GET" statusCallbackEvent="start end join leave mute hold">' . $_POST['CallSid'] . '</Conference></Dial></Response>'
                    )
                );
                $childCall = $client->calls($calls->parentCallSid)
                    ->fetch();
                $resp_data['callSid'] = $calls->sid;
                $resp_data['number'] = $childCall->from;
            }
        }
        $activity_data['staff_id'] = $lead ? $lead->assigned : 0;
        $activity_data['date'] = date("Y-m-d H:i:s");
        $activity_data['description'] = $_POST['CallSid'];
        $activity_data['additional_data'] = json_encode($this->input->post());
        $response = $this->lead_manager_model->lead_manger_activity_log($activity_data);
        $resp_data['status'] = $_POST['CallSid'];
        print(json_encode($resp_data));
    }
    public function connfrenceCallBack($direction = '')
    {
        $call_back_data = array(
            'ConferenceSid' => $this->input->get('ConferenceSid'),
            'FriendlyName' => $this->input->get('FriendlyName'),
            'StatusCallbackEvent' => $this->input->get('StatusCallbackEvent'),
            'CallSid' => !is_null($this->input->get('CallSid')) ? $this->input->get('CallSid') : NULL,
            'Muted' => !is_null($this->input->get('Muted')) ? $this->input->get('Muted') : NULL,
            'Hold' => !is_null($this->input->get('Hold')) ? $this->input->get('Hold') : NULL,
            'direction' => $direction
        );
        $this->load->model('lead_manager_model');
        $this->lead_manager_model->createConfrenceCallBack($call_back_data);
    }

    public function addConfParticep()
    {
        $from = $_POST['callerIdNumber'];
        $to = $_POST['phoneNumber'];
        $freindlyName = $_POST['freindlyName'];
        $this->load->model('lead_manager_model');
        $active_confrence = $this->lead_manager_model->get_conference($freindlyName);
        $client = new Client(get_option('call_twilio_account_sid'), get_option('call_twilio_auth_token'));
        try {
            $participant = $client->conferences($active_confrence->ConferenceSid)
                ->participants
                ->create($from, $to);
            $resp_data['callSid'] = $participant->callSid;
            $resp_data['number'] = $to;
            print(json_encode($resp_data));
        } catch (Exception $e) {
            print(json_encode(array('error' => $e->getMessage())));
        }
    }


    public function busyIncommingCalls()
    {
        $now = new DateTime();
        $todayDate = $now->format('Y-m-d');
        $dateObj = $todayDate . 'T00:00:00Z';
        $sid  = get_option('call_twilio_account_sid');
        $token  = get_option('call_twilio_auth_token');
        $twilio = new Client($sid, $token);
        $calls = $twilio->calls->read(["direction" => "inbound-dial", 'startTimeAfter' => new \DateTime($dateObj)], 10);
        $i = 0;
        $data = [];
        if (isset($calls) && !empty($calls)) {
            foreach ($calls as $record) {
                $staffId = $this->get_staff_by_twilio_number($record->to);
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
                    $pcalls = $twilio->calls->read(["parentCallSid" => $data[$i]['sid']], 1);
                    if (isset($pcalls) && !empty($pcalls)) {
                        foreach ($pcalls as $childcall) {
                            $data[$i]['child_status'] = $childcall->status;
                        }
                    }
                    if ($data[$i]['status'] == 'busy' || $data[$i]['child_status'] == 'no-answer' || $data[$i]['status'] == 'failed' || $data[$i]['child_status'] == 'failed' || $data[$i]['child_status'] == 'busy' || $data[$i]['status'] == 'no-answer') {
                        $this->addMissedCalls($data, $i);
                    }
                }
                $i++;
            }
        }
    }

    public function get_staff_by_twilio_number($number)
    {
        if ($number) {
            $CI = &get_instance();
            $CI->db->select('relid');
            $CI->db->where(['value' => $number, 'fieldto' => 'staff']);
            $res = $CI->db->get(db_prefix() . 'customfieldsvalues')->row();
            return ($res) ? $res->relid : '0';
        }
        return false;
    }
    public function get_lead_name_by_number($number)
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

    public function addMissedCalls($data, $j)
    {
        $CI = &get_instance();
        $CI->db->where('call_sid', $data[$j]['sid']);
        $q = $CI->db->get(db_prefix() . 'lead_manager_missed_calls');
        $leadName = $CI->get_lead_name_by_number($data[$j]['from']);
        if ($q->num_rows() == 0) {
            $insert_data = array(
                'staff_id' => $data[$j]['staff_id'],
                'call_sid' => $data[$j]['sid'],
                'staff_twilio_number' => $data[$j]['to'],
                'date' => to_sql_date($data[$j]['dateCreated'], true),
            );
            $CI->db->insert(db_prefix() . 'lead_manager_missed_calls', $insert_data);
            $notifcationArr = array(
                'isread' => 0,
                'isread_inline' => 0,
                'date' => to_sql_date($data[$j]['dateCreated'], true),
                'description' => 'You have missed call from: ' . $data[$j]['from'] . ' at ' . to_sql_date($data[$j]['dateCreated'], true),
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
                $notifcationArr['description']  = 'You have missed call from: ' . $data[$j]['from'];
                $CI->load->model('lead_manager_model');
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
            }
            $CI->db->insert(db_prefix() . 'notifications', $notifcationArr);
        }
    }

    public function getFromNumberByChildCallSid()
    {
        $childCallSid = $_POST["CallSid"];
        $data = [];
        if ($childCallSid) {
            $sid  = get_option('call_twilio_account_sid');
            $token  = get_option('call_twilio_auth_token');
            $twilio = new Client($sid, $token);
            $childCall = $twilio->calls($childCallSid)->fetch();
            $parentCallSid = $childCall->parentCallSid;
            if ($parentCallSid) {
                $pcalls = $twilio->calls($parentCallSid)->fetch();
                if (isset($pcalls) && !empty($pcalls)) {
                    $leadName = $this->get_lead_name_by_number($pcalls->from);
                    if ($leadName) {
                        $data['from'] = $leadName;
                    } else {
                        $data['from'] = $pcalls->from;
                    }
                    $data['to'] = $pcalls->to;
                    $data['fromNumber'] = $pcalls->from;
                    $data['sid'] = $parentCallSid;
                }
                echo json_encode($data);
                exit;
            }
        }
    }

    public function active_twilio_account()
    {
        $sid  = get_option('call_twilio_account_sid');
        $token  = get_option('call_twilio_auth_token');
        $response = array();
        $twilio = new Client($sid, $token);
        $incomingPhoneNumbers = $twilio->incomingPhoneNumbers
            ->read([]);
        $response['numbers'] = count($incomingPhoneNumbers);
        $account = $twilio->api->v2010->accounts($sid)
            ->fetch();
        $response['balance'] = $this->active_twilio_account_curl($account->subresourceUris['balance']);
        return $response;
    }

    public function active_twilio_account_curl($url)
    {
        $sid  = get_option('call_twilio_account_sid');
        $token  = get_option('call_twilio_auth_token');
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.twilio.com/' . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic QUMxZTQ5MTkyOGFlYzFkZGFjODJmNDlkNWJiZjEzZjYxNjo3Mzg3ZmJhN2YyNDZhMWJjZjQyZWY1MGE5MTE2OGE0Ng=='
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }
    public function deleteConferanceCall()
    {
        $client = new Client(get_option('call_twilio_account_sid'), get_option('call_twilio_auth_token'));
        $freindlyName = $_POST['freindlyName'];
        $callSid = $_POST['callsid'];
        $this->load->model('lead_manager_model');
        $active_confrence = $this->lead_manager_model->get_conference($freindlyName);
        $client->conferences($active_confrence->ConferenceSid)
            ->participants($callSid)
            ->delete();
        echo true;
    }
    public function updateConferanceCall()
    {
        $client = new Client(get_option('call_twilio_account_sid'), get_option('call_twilio_auth_token'));
        $freindlyName = $_POST['freindlyName'];
        $callSid = $_POST['callsid'];
        $type = $_POST['action_type'];
        $update_action = [];
        $resp = [];
        if ($type == 'mute') {
            $update_action['muted'] = True;
            $resp['action'] = 'muted';
        } else if ($type == 'hold') {
            $update_action['hold'] = True;
            $update_action['holdUrl'] = 'https://api.twilio.com/cowbell.mp3';
            $resp['action'] = 'unhold';
        } else if ($type == 'unhold') {
            $update_action['hold'] = False;
            $resp['action'] = 'hold';
        } else if ($type == 'muted') {
            $update_action['muted'] = False;
            $resp['action'] = 'mute';
        }
        $this->load->model('lead_manager_model');
        $active_confrence = $this->lead_manager_model->get_conference($freindlyName);
        $client->conferences($active_confrence->ConferenceSid)
            ->participants($callSid)
            ->update($update_action);
        $resp['type'] = $type;
        echo json_encode($resp);
        die();
    }

    // public function transferCall()
    // {
    //     $leadid = 0;
    //     $is_lead = '';
    //     $client_id = '';
    //     if ($this->input->post()) {
    //         $response = new VoiceResponse();
    //         $callerIdNumber = $this->input->post('callerIdNumber');
    //         $calleeNumber = $this->input->post('calleeNumber');
    //         $staff_twilio_number = $this->get_staff_own_twilio_number($this->input->post('staffid'));
    //         if (isset($callerIdNumber)) {
    //             if (get_option('call_twilio_recording_active')) {
    //                 header('Content-Type: text/xml');
    //                 echo "<Response><Dial>" . $staff_twilio_number . "</Dial></Response>";
    //                 die;
    //             } else {
    //                 $dial = $response->dial('', ['callerId' => $callerIdNumber]);
    //                 if (isset($phoneNumberToDial)) {
    //                     $call_direction = 'outgoing';
    //                     $dial->number($phoneNumberToDial, ['statusCallback' => base_url() . 'lead_manager/Call_control/handleNotRecordedOutgoingCall/' . $leadid . '?call_direction=' . $call_direction . '&is_lead=' . $is_lead . '&client_id=' . $client_id, 'statusCallbackMethod' => 'GET']);
    //                 } else {
    //                     $call_direction = 'incoming';
    //                     $dial->client('support_agent', ['statusCallback' => base_url() . 'lead_manager/Call_control/handleNotRecordedIncomingCall/' . $leadid . '?call_direction=' . $call_direction . '&is_lead=' . $is_lead . '&client_id=' . $client_id, 'statusCallbackMethod' => 'GET']);
    //                 }
    //                 header('Content-Type: text/xml');
    //                 echo $response;
    //             }
    //         }
    //     }
    // }
    public function addConfParticepIncoming()
    {
        $lead = NULL;
        if ($this->input->post() && $this->input->is_ajax_request()) {
            $client = new Client(get_option('call_twilio_account_sid'), get_option('call_twilio_auth_token'));
            $active_confrence = $this->lead_manager_model->get_conference($_POST['freindlyName']);
            $calls = $client->calls($_POST['CallSid'])
                ->fetch();
            //print_r($calls); die;
            if (isset($calls) && !empty($calls)) {
               
               // $parentCall = $client->calls($calls->parentCallSid);

                 //print_r($parentCall); die; 
               
                $activity_data['lead_id'] = $this->get_lead_id_by_number($calls->from);
                if (isset($activity_data['lead_id']) && !empty($activity_data['lead_id'])) {
                    $lead = $this->lead_manager_model->get($activity_data['lead_id']);
                } else {
                    $lead = $this->lead_manager_model->get_contact('', ['is_primary' => 1, 'phonenumber' => $calls->from]);
                    if (!isset($lead) && empty($lead)) {
                        $lead = $this->lead_manager_model->get_client('', ['active' => 1, 'phonenumber' => $calls->from]);
                        if (isset($lead) && !empty($lead)) {
                            $activity_data['lead_id'] = $lead->user_id;
                        }
                    }
                    if (isset($lead) && !empty($lead)) {
                        $activity_data['lead_id'] = $lead->user_id;
                    }
                    $activity_data['is_client'] = 1;
                }
                $activity_data['direction'] = 'incoming';
                $client->calls($calls->parentCallSid)->update(
                    array(
                        'twiml' => '<Response><Dial><Conference statusCallback="' . base_url('lead_manager/Call_control/connfrenceCallBack/incoming') . '" statusCallbackMethod="GET" statusCallbackEvent="start end join leave mute hold">' . $active_confrence->ConferenceSid . '</Conference></Dial></Response>'
                    )
                );
                // $client->calls($calls->parentCallSid)->update(
                //     array(
                //         'twiml' => '<Response><Dial><Conference statusCallback="' . base_url('lead_manager/Call_control/connfrenceCallBack/incoming') . '" statusCallbackMethod="GET" statusCallbackEvent="start end join leave mute hold">' . $active_confrence->ConferenceSid . '</Conference></Dial></Response>'
                //     )
                // );
            }
        }
        $activity_data['staff_id'] = $lead ? $lead->assigned : 0;
        $activity_data['date'] = date("Y-m-d H:i:s");
        $activity_data['description'] = $_POST['CallSid'];
        $activity_data['additional_data'] = json_encode($this->input->post());
        $response = $this->lead_manager_model->lead_manger_activity_log($activity_data);
        $resp_data['status'] = $_POST['CallSid'];
        print(json_encode($resp_data));
    }
}
