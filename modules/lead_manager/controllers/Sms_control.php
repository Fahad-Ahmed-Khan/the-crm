<?php defined('BASEPATH') or exit('No direct script access allowed');
$check =  __dir__;
$str = preg_replace('/\W\w+\s*(\W*)$/', '$1', $check);
$str . '/third_party/twilio-web/src/Twilio/autoload.php';

use Twilio\TwiML\MessagingResponse;

class Sms_control extends CI_Controller
{

    protected $pusher;
    protected $pusher_options = array();

    public function __construct()
    {
        parent::__construct();
        $this->pusher_options['app_key'] = get_option('pusher_app_key');

        $this->pusher_options['app_secret'] = get_option('pusher_app_secret');

        $this->pusher_options['app_id'] = get_option('pusher_app_id');
        if (get_option('pusher_cluster') != '') {

            $this->pusher_options['cluster'] = get_option('pusher_cluster');
        }
        if (

            get_option('pusher_app_key') != '' ||

            get_option('pusher_app_secret') != '' ||

            get_option('pusher_app_id') != '' ||

            get_option('pusher_cluster') != ''

        ) {
            $this->pusher = new Pusher\Pusher(

                $this->pusher_options['app_key'],

                $this->pusher_options['app_secret'],

                $this->pusher_options['app_id'],

                array('cluster' => $this->pusher_options['cluster'])

            );
        }
    }

    public function Incoming_sms()
    {
        $this->load->helper('lead_manager');
        $insert = [];
        if ($this->input->get()) {
            $insert['msg_service_id'] = $this->input->get('MessagingServiceSid');
            $insert['msg_sid'] = $this->input->get('MessageSid');
            $insert['from_number'] = $this->input->get('From');
            $insert['to_number'] = $this->input->get('To');
            $insert['from_id'] = get_lead_id_by_number($this->input->get('From'));
            $insert['to_id'] = get_staff_by_twilio_number($this->input->get('To'));
            $insert['sms_direction'] = 'incoming';
            $insert['sms_status'] = $this->input->get('SmsStatus');
            $insert['sms_body'] = $this->input->get('Body');
            $insert['api_response'] = json_encode($this->input->get());
            $CI = &get_instance();
            $res = $CI->db->insert(db_prefix() . 'lead_manager_conversation', $insert);
            die($res);
        }
    }
    public function Incoming_sms_failed()
    {
        $insert = [];
        if ($this->input->get()) {
            $insert['api_response'] = json_encode($this->input->get());
            $CI = &get_instance();
            $res = $CI->db->insert(db_prefix() . 'lead_manager_conversation', $insert);
            die($res);
        }
    }

    public function incoming_sms_status_webhook()
    {
        if ($this->input->post()) {
            $conversation = '';
            $post_data = $this->input->post();
            $CI = &get_instance();
            $CI->db->where(['msg_sid' => $post_data['MessageSid']]);
            $CI->db->update(db_prefix() . 'lead_manager_conversation', ['sms_status' => $post_data['SmsStatus']]);
            if ($CI->db->affected_rows() > 0) {
                $CI->db->where(['msg_sid' => $post_data['MessageSid']]);
                $conversation =  $CI->db->get(db_prefix() . 'lead_manager_conversation')->row();
                if(isset($conversation) && !empty($conversation)){
                    $sms_event_array = array(
                        'msg_id' => $conversation->id,
                        'sms_status' => $conversation->sms_status,
                        'from' => $conversation->to_id
                    );
                    $this->pusher->trigger(
                        'lead-manager-chanel',
                        'sms-event',
                        $sms_event_array
                    );
                }
            }
        }
    }
    public function handleReply()
    {
        if ($this->input->post()) {
            $post_data = $this->input->post();
            $CI = &get_instance();
            $CI->db->where(['msg_sid' => $post_data['MessageSid']]);
            $CI->db->update(db_prefix() . 'lead_manager_conversation', ['sms_status' => $post_data['SmsStatus']]);
            echo $CI->db->affected_rows();
        }
    }

    // public function send_sms()
    // {
    //     $response = new MessagingResponse();
    //     $response->message('hello how are you', ['to' => '+919453974798', 'from' => '+18044947745', 'action' => 'https://zonvoirdemo.in/newcrm/admin/lead_manager/sms_control/status_send_sms', 'method' => 'GET']);
    //     header('Content-Type: text/xml');
    //     echo $response;
    // }
    public function status_send_sms()
    {
        echo $this->input->get('MessageStatus');
    }

    public function incoming_sms_webhook()
    {
        $this->load->helper('lead_manager');
        $this->load->helper('general_helper');
        $insert = [];
        $res = 0;
        $insert_id = NULL;
        $is_client = 0;
        $todayDate = date("Y-m-d H:i:s");
        if (!class_exists('lead_manager_model')) {
            $this->load->model('lead_manager_model');
        }
        if ($this->input->get()) {
            $clientid = get_client_id_by_number($this->input->get('From'));
            $leadid = get_lead_id_by_number($this->input->get('From'));
            if ($leadid) {
                $insert['msg_sid'] = $this->input->get('SmsMessageSid');
                $insert['from_number'] = $this->input->get('From');
                $insert['to_number'] = $this->input->get('To');
                $insert['from_id'] = $leadid;
                $insert['to_id'] = get_staff_by_twilio_number($this->input->get('To'));
                $insert['sms_direction'] = 'incoming';
                $insert['sms_status'] = $this->input->get('SmsStatus');
                $insert['sms_body'] = $this->input->get('Body');
                $insert['api_response'] = json_encode($this->input->get());
                $insert['is_client'] = 0;
                $insert['is_read'] = 'no';
                $insert['sms_date'] = to_sql_date($todayDate, true);
                $CI = &get_instance();
                $res = $CI->db->insert(db_prefix() . 'lead_manager_conversation', $insert);
                $insert_id = $CI->db->insert_id();
            } else if ($clientid) {
                $is_client = 1;
                $insert['msg_sid'] = $this->input->get('SmsMessageSid');
                $insert['from_number'] = $this->input->get('From');
                $insert['to_number'] = $this->input->get('To');
                $insert['from_id'] = $clientid;
                $insert['to_id'] = get_staff_by_twilio_number($this->input->get('To'));
                $insert['sms_direction'] = 'incoming';
                $insert['sms_status'] = $this->input->get('SmsStatus');
                $insert['sms_body'] = $this->input->get('Body');
                $insert['api_response'] = json_encode($this->input->get());
                $insert['is_client'] = 1;
                $insert['is_read'] = 'no';
                $insert['sms_date'] = to_sql_date($todayDate, true);
                $CI = &get_instance();
                $res = $CI->db->insert(db_prefix() . 'lead_manager_conversation', $insert);
                $insert_id = $CI->db->insert_id();
            } else {
                $staffid = get_staff_by_twilio_number($this->input->get('To'));
                $data = array(
                    'description' => 'New Incoming sms message id' . $this->input->get('SmsMessageSid'),
                    'addedfrom' => $staffid,
                    'address' => NULL,
                    'email' => NULL,
                    'phonenumber' => $this->input->get('From'),
                    'name' => 'New lead',
                    'status' => 2,
                    'source' => 1,
                    'lastcontact' => to_sql_date($todayDate, true),
                );
                $leadid = $this->lead_manager_model->add_lead($data);
                $insert['msg_sid'] = $this->input->get('SmsMessageSid');
                $insert['from_number'] = $this->input->get('From');
                $insert['to_number'] = $this->input->get('To');
                $insert['from_id'] = $leadid;
                $insert['to_id'] = $staffid;
                $insert['sms_direction'] = 'incoming';
                $insert['sms_status'] = $this->input->get('SmsStatus');
                $insert['sms_body'] = $this->input->get('Body');
                $insert['api_response'] = json_encode($this->input->get());
                $insert['is_client'] = 0;
                $insert['is_read'] = 'no';
                $insert['sms_date'] = to_sql_date($todayDate, true);
                $CI = &get_instance();
                $res = $CI->db->insert(db_prefix() . 'lead_manager_conversation', $insert);
                $insert_id = $CI->db->insert_id();
            }
            $sms_event_array = array(

                'sms_id' => $insert_id,

                'profile_image' => staff_profile_image_url($insert['to_id']),

                'message' => $insert['sms_body'],

                'from' => $insert['from_id'],

                'to' => $insert['to_id'],

                'is_client' => $is_client,

                'time' => _dt($insert['sms_date']),

                'sms_status' => $insert['sms_status']

            );
            $this->pusher->trigger(

                'lead-manager-chanel',

                'sms-event',

                $sms_event_array

            );
            $this->pusher->trigger(

                'lead-manager-notifications-channel-' . $insert['to_id'],

                'sms_notification',

                array(
                    'total_unread' => $this->lead_manager_model->get_total_unread_sms_by_staff($insert['to_id']),
                    'messages' => $this->lead_manager_model->get_unread_sms_by_staff($insert['to_id'])
                )

            );
        }
    }
    public function incoming_whatsapp_webhook()
    {
        $this->load->helper('lead_manager');
        $insert = [];
        $res = 0;
        $from_num = str_replace('whatsapp:', '', $this->input->post('From'));
        $to_num = str_replace('whatsapp:', '', $this->input->post('To'));
        $is_client = 0;
        $insert_id = NULL;
        $todayDate = date("Y-m-d H:i:s");
        if ($this->input->post()) {
            $post_data = $this->input->post();
            $clientid = get_client_id_by_number($from_num);
            $leadid = get_lead_id_by_number($from_num);
            if ($leadid) {
                if (isset($post_data['NumMedia']) && $post_data['NumMedia']) {
                    $insert['is_files'] = $this->getMultimediaMessage($post_data, $leadid, get_staff_by_twilio_number($to_num), 'lead');
                }
                $insert['msg_sid'] = $post_data['SmsMessageSid'];
                $insert['from_number'] = $from_num;
                $insert['to_number'] = $to_num;
                $insert['from_id'] = $leadid;
                $insert['to_id'] = get_staff_by_twilio_number($to_num);
                $insert['sms_direction'] = 'incoming';
                $insert['sms_status'] = $post_data['SmsStatus'];
                $insert['sms_body'] = $post_data['Body'];
                $insert['api_response'] = json_encode($post_data);
                $insert['is_client'] = 0;
                $insert['is_read'] = 'no';
                $insert['sms_date'] = to_sql_date($todayDate, true);
                $CI = &get_instance();
                $res = $CI->db->insert(db_prefix() . 'lead_manager_whatsapp', $insert);
                $insert_id = $CI->db->insert_id();
            } else if ($clientid) {
                if (isset($post_data['NumMedia']) && $post_data['NumMedia']) {
                    $insert['is_files'] = $this->getMultimediaMessage($post_data, $leadid, get_staff_by_twilio_number($to_num), 'client');
                }
                $is_client = 1;
                $insert['msg_sid'] = $post_data['SmsMessageSid'];
                $insert['from_number'] = $from_num;
                $insert['to_number'] = $to_num;
                $insert['from_id'] = $clientid;
                $insert['to_id'] = get_staff_by_twilio_number($to_num);
                $insert['sms_direction'] = 'incoming';
                $insert['sms_status'] = $post_data['SmsStatus'];
                $insert['sms_body'] = $post_data['Body'];
                $insert['api_response'] = json_encode($post_data);
                $insert['is_client'] = $is_client;
                $insert['is_read'] = 'no';
                $insert['sms_date'] = to_sql_date($todayDate, true);
                $CI = &get_instance();
                $res = $CI->db->insert(db_prefix() . 'lead_manager_whatsapp', $insert);
                $insert_id = $CI->db->insert_id();
            } else {
                $staffid = get_staff_by_twilio_number($to_num);
                $this->load->model('lead_manager_model');
                $data = array(
                    'description' => 'New Incoming sms message id' . $post_data['SmsMessageSid'],
                    'addedfrom' => $staffid,
                    'address' => NULL,
                    'email' => NULL,
                    'phonenumber' => $from_num,
                    'name' => 'New lead',
                    'status' => 2,
                    'source' => 1,
                    'lastcontact' => date("Y-m-d H:i:s"),
                );
                $leadid = $this->lead_manager_model->add_lead($data);
                if (isset($post_data['NumMedia']) && $post_data['NumMedia']) {
                    $insert['is_files'] = $this->getMultimediaMessage($post_data, $leadid, get_staff_by_twilio_number($to_num), 'lead');
                }
                $insert['msg_sid'] = $post_data['SmsMessageSid'];
                $insert['from_number'] = $from_num;
                $insert['to_number'] = $to_num;
                $insert['from_id'] = $leadid;
                $insert['to_id'] = $staffid;
                $insert['sms_direction'] = 'incoming';
                $insert['sms_status'] = $post_data['SmsStatus'];
                $insert['sms_body'] = $post_data['Body'];
                $insert['api_response'] = json_encode($post_data);
                $insert['is_client'] = 0;
                $insert['is_read'] = 'no';
                $insert['sms_date'] = to_sql_date($todayDate, true);
                $CI = &get_instance();
                $res = $CI->db->insert(db_prefix() . 'lead_manager_whatsapp', $insert);
                $insert_id = $CI->db->insert_id();
                echo $res;
                die;
            }
            $whatsapp_event_array = array(

                'sms_id' => $insert_id,

                'profile_image' => staff_profile_image_url($insert['to_id']),

                'message' => $insert['sms_body'],

                'from' => $insert['from_id'],

                'to' => $insert['to_id'],

                'is_client' => $is_client,

                'time' => date('Y-m-d H:i:s'),

                'sms_status' => $insert['sms_status']

            );

            if (isset($insert['is_files'])) {
                $this->load->model('lead_manager_model');
                $whatsapp_event_array['file'] = $this->lead_manager_model->get_whatsapp_file($insert['is_files']);
            }
            $this->pusher->trigger(

                'lead-manager-chanel',

                'whatsapp-event',

                $whatsapp_event_array

            );
            $this->pusher->trigger(

                'lead-manager-notifications-channel-' . $insert['to_id'],

                'whatsapp_notification',

                array()

            );
        }
    }
    public function incoming_whatsapp_status()
    {
        if ($this->input->post()) {
            $this->load->model('lead_manager_model');
            if ($this->lead_manager_model->update_whatsapp_msg($this->input->post())) {
                $message = $this->lead_manager_model->get_whatsapp_msg_row(['msg_sid' => $this->input->post('SmsSid')]);
                $whatsapp_status_event_array = (array) $message;
                if (isset($message) && !empty($message)) {
                    $this->pusher->trigger(

                        'lead-manager-chanel',

                        'whatsapp-status-event',

                        $whatsapp_status_event_array

                    );
                }
            }
        }
    }
    public function getMultimediaMessage($data, $fromid, $toid, $type)
    {
        $file_insert['from_id'] = $fromid;
        $file_insert['to_id'] = $toid;
        $file_insert['file_name'] = '';
        $file_insert['filetype'] = $data['MediaContentType0'];
        $file_insert['type'] = $type;
        $mmsFileContent = getTwilioMediaContent($data['MediaUrl0']);
        $extension = mime2ext($data['MediaContentType0']);
        $path = FCPATH . 'uploads/lead_manager/whatsapp/' . $type . '/' . $fromid . '/' . $toid . '/';
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
        $fileName = $data['MessageSid'] . '.' . $extension;
        $mmsFileWrite = fopen($path . $fileName, 'w');
        fwrite($mmsFileWrite, $mmsFileContent);
        $file_insert['file_name'] = $fileName;
        $file_insert['dateadded'] = date('Y-m-d H:i:s');
        $CI = &get_instance();
        $CI->db->insert(db_prefix() . 'lead_manager_whatsapp_files', $file_insert);
        return $CI->db->insert_id();
    }
}
