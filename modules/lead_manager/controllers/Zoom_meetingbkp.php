<?php defined('BASEPATH') or exit('No direct script access allowed');
$check =  __dir__;
$str = preg_replace('/\W\w+\s*(\W*)$/', '$1', $check);
$str .= '/vendor/autoload.php';
require_once($str);

use Firebase\JWT\JWT;
use GuzzleHttp\Client;

class Zoom_meeting extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('lead_manager_model');
        $this->load->model('clients_model');
        $this->load->helper('lead_manager');
        $this->load->library('mails/lead_manager_mail_template');
        $this->load->library('mails/app_mail_template');
        // $this->load->library('merge_fields/lead_manager_merge_fields');
    }
    function getZoomAccessToken()
    {
        $key = get_option('zoom_secret_key');
        $payload = array(
            "iss" => get_option('zoom_api_key'),
            'exp' => time() + 3600,
        );
        return JWT::encode($payload, $key,'HS256');
    }
    function createZoomMeeting()
    {
        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://api.zoom.us',
        ]);
        $data = $this->input->post();
        if (isset($data['is_client']) && $data['is_client']) {
            $staff = get_staff($data['staff_name']);
            $data['staff_email'] = $staff->email;
            $data['staff_id'] = $data['staff_name'];
            $data['staff_name'] = $staff->full_name;
        } else {
            $data['is_client'] = 0;
        }
        $settings = array();
        $json = array();
        if (isset($data['meeting_option'])) {
            if (!is_bool(array_search("allow_participants_to_join_anytime", $data['meeting_option']))) {
                $settings["join_before_host"] = TRUE;
            }
            if (!is_bool(array_search("mute_participants_upon_entry", $data['meeting_option']))) {
                $settings["mute_upon_entry"] = TRUE;
            }
            if (!is_bool(array_search("automatically_record_meeting_on_the_local_computer", $data['meeting_option']))) {
                $settings["audio"] = "both";
                $settings["auto_recording"] = "local";
            }
            $json = [
                "topic" => $data['meeting_agenda'],
                "type" => 2,
                "start_time" => $data['meeting_start_date'],
                "duration" => $data['meeting_duration'], // 30 mins
                "password" => "123456",
                "timezone" => $data['zoom_timezone'],
                "settings" => $settings
            ];
        } else {
            $settings["auto_recording"] = "none";
            $data['meeting_option'] = array();
            $json = [
                "topic" => $data['meeting_agenda'],
                "type" => 2,
                "start_time" => $data['meeting_start_date'],
                "duration" => $data['meeting_duration'], // 30 mins
                "password" => "123456",
                "timezone" => $data['zoom_timezone'],
            ];
        }
        $response = $client->request('POST', '/v2/users/me/meetings', [
            "headers" => [
                "Authorization" => "Bearer " . $this->getZoomAccessToken()
            ],
            'json' => $json,
        ]);
        $meeting_res_data = json_decode($response->getBody());
        $response = $this->lead_manager_model->save_zoom_meeting($data, $meeting_res_data);
        echo $response; die();
    }
    function updateZoomMeeting($meeting_id)
    {
        $client = new Client([
            'base_uri' => 'https://api.zoom.us',
        ]);
        $response = $client->request('PATCH', '/v2/meetings/' . $meeting_id, [
            "headers" => [
                "Authorization" => "Bearer " . getZoomAccessToken()
            ],
            'json' => [
                "topic" => "Let's Learn Laravel",
                "type" => 2,
                "start_time" => "2021-07-20T10:30:00",
                "duration" => "45", // 45 mins
                "password" => "123456"
            ],
        ]);
        if (204 == $response->getStatusCode()) {
            echo "Meeting is updated successfully.";
        }
    }

    public function update_meeting_status()
    {
        if ($this->input->post() && $this->input->is_ajax_request()) {
            $meeting = $this->lead_manager_model->zoomMeetingDetails($this->input->post('id'));
            $status = $this->input->post('status') == 0 ? 'end' : ($this->input->post('status') == 1 ? 'waiting' : '');
            $post_data = ['id' => $this->input->post('id'), 'status' => $status];
            if (isset($meeting) && !empty($meeting->meeting_id)) {
                if ($status) {
                    $apiresponse = $this->updateZoomMeetingStatus($meeting->meeting_id, $status);
                    if ($apiresponse == 204) {
                        $this->lead_manager_model->update_meeting_status($post_data);
                    }
                }
            }
        }
    }
    public function save_meeting_remark()
    {
        $res = $this->lead_manager_model->save_meeting_remark($this->input->post());
        echo $res;
    }
    public function show_remark_modal()
    {
        $id = $this->input->get('id');
        $rel_type = $this->input->get('rel_type');
        $data['meeting_id'] = $id;
        $data['rel_type'] = $rel_type;
        $view = $this->load->view('lead_manager/save_meeting_remark', $data, true);
        echo $view;
        exit();
    }
    public function showMeetingRemark()
    {
        $id = $this->input->get('id');
        $rel_type = $this->input->get('rel_type');
        $data['zoom_meeting_remarks']         = $this->lead_manager_model->zoom_meeting_remarksDetails($id, $rel_type);
        $view = $this->load->view('lead_manager/show_meeting_remark', $data, true);
        echo $view;
        exit();
    }
    function zoomMeetingDetails()
    {
        $id = $this->input->get('id');
        $data['meeting_details']         = $this->lead_manager_model->zoomMeetingDetails($id);
        $view = $this->load->view('lead_manager/zoom_meeting_details', $data, true);
        echo $view;
        exit();
    }
    public function delete_zoom_meeting($id)
    {
        if (!$id) {
            redirect(admin_url('lead_manager/zoom_meeting'));
        }
        $response = false;
        $meeting = $this->lead_manager_model->zoomMeetingDetails($id);
        $apiresponse = $this->deleteZoomMeeting($meeting->meeting_id);
        if ($apiresponse == 204) {
            $response = $this->lead_manager_model->delete_zoom_meeting($id);
        }
        if ($response === true) {
            set_alert('success', _l('deleted', _l('zoom_meeting')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('lead_lowercase')));
        }

        $ref = $_SERVER['HTTP_REFERER'];
        redirect($ref);
    }

    function deleteZoomMeeting($meeting_id)
    {
        $client = new Client([
            'base_uri' => 'https://api.zoom.us',
        ]);
        $response = 204;
        try {
            $response = $client->request("DELETE", "/v2/meetings/$meeting_id", [
                "headers" => [
                    "Authorization" => "Bearer " . $this->getZoomAccessToken()
                ]
            ]);
        } catch (GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            
        }
        return $response->getStatusCode();
    }
    function updateZoomMeetingStatus($meeting_id, $status)
    {
        $client = new Client([
            'base_uri' => 'https://api.zoom.us',
        ]);
        $response = $client->request('PUT', '/v2/meetings/' . $meeting_id . '/status', [
            "headers" => [
                "Authorization" => "Bearer " . $this->getZoomAccessToken()
            ],
            'json' => [
                "action" => $status
            ],
        ]);
        return $response->getStatusCode();
    }
    function getZoomMeeting($meeting_id)
    {
        $client = new Client([
            'base_uri' => 'https://api.zoom.us',
        ]);
        $response = $client->request('GET', '/v2/meetings/' . $meeting_id, [
            "headers" => [
                "Authorization" => "Bearer " . $this->getZoomAccessToken()
            ],
        ]);
        return json_decode($response->getBody());
    }
    function getZoomMeetingRegistrants($meeting_id)
    {
        $client = new Client([
            'base_uri' => 'https://api.zoom.us',
        ]);
        $response = $client->request('GET', '/v2/meetings/' . $meeting_id . '/registrants', [
            "headers" => [
                "Authorization" => "Bearer " . $this->getZoomAccessToken()
            ],
        ]);
        return $response->getBody();
    }
}
