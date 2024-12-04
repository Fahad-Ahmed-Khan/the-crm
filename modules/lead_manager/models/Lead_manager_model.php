<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Lead_manager_model extends App_Model

{
    private $attachment = [];

    public function __construct()
    {
        parent::__construct();
    }
    private function lm_clear_attachments()
    {
        $this->attachment = [];
    }
    public function get($id = '', $where = [], $order_by = [])
    {
        $this->db->select('*,' . db_prefix() . 'leads.name, ' . db_prefix() . 'leads.id,' . db_prefix() . 'leads_status.name as status_name,' . db_prefix() . 'leads_sources.name as source_name');
        $this->db->join(db_prefix() . 'leads_status', db_prefix() . 'leads_status.id=' . db_prefix() . 'leads.status', 'left');
        $this->db->join(db_prefix() . 'leads_sources', db_prefix() . 'leads_sources.id=' . db_prefix() . 'leads.source', 'left');
        $this->db->join(db_prefix() . 'lead_manager_conversation as chats', '(chats.from_id=' . db_prefix() . 'leads.id OR chats.to_id=' . db_prefix() . 'leads.id) AND chats.is_client=0 AND chats.id = (SELECT MAX(id) FROM ' . db_prefix() . 'lead_manager_conversation WHERE from_id = ' . db_prefix() . 'leads.id OR to_id = ' . db_prefix() . 'leads.id)', 'left');
        if (isset($where) && !empty($where)) {
            $this->db->where($where);
        }
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'leads.id', $id);
            $lead = $this->db->get(db_prefix() . 'leads')->row();
            if ($lead) {
                if ($lead->from_form_id != 0) {
                    $lead->form_data = $this->get_form([
                        'id' => $lead->from_form_id,
                    ]);
                }
                $lead->attachments = $this->get_lead_attachments($id);
                $lead->public_url  = leads_public_url($id);
            }
            return $lead;
        }
        if (isset($order_by) && !empty($order_by)) {
            $this->db->order_by($order_by['cond'], $order_by['order']);
        }
        return $this->db->get(db_prefix() . 'leads')->result_array();
        //  echo $this->db->last_query(); die;
    }

    public function get_source($id = false)
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'leads_sources')->row();
        }

        $this->db->order_by('name', 'asc');

        return $this->db->get(db_prefix() . 'leads_sources')->result_array();
    }

    public function get_status($id = '', $where = [])
    {
        $this->db->where($where);
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'leads_status')->row();
        }

        $statuses = $this->app_object_cache->get('leads-all-statuses');

        if (!$statuses) {
            $this->db->order_by('statusorder', 'asc');

            $statuses = $this->db->get(db_prefix() . 'leads_status')->result_array();
            $this->app_object_cache->add('leads-all-statuses', $statuses);
        }

        return $statuses;
    }


    public function update_status($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'leads_status', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Leads Status Updated [StatusID: ' . $id . ', Name: ' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    public function update_lead_status($data)
    {
        $this->db->select('status');
        $this->db->where('id', $data['leadid']);
        $_old = $this->db->get(db_prefix() . 'leads')->row();

        $old_status = '';

        if ($_old) {
            $old_status = $this->get_status($_old->status);
            if ($old_status) {
                $old_status = $old_status->name;
            }
        }

        $affectedRows   = 0;
        $current_status = $this->get_status($data['status'])->name;

        $this->db->where('id', $data['leadid']);
        $this->db->update(db_prefix() . 'leads', [
            'status' => $data['status'],
        ]);

        $_log_message = '';

        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
            if ($current_status != $old_status && $old_status != '') {
                $_log_message    = 'not_lead_activity_status_updated';
                $additional_data = serialize([
                    get_staff_full_name(),
                    $old_status,
                    $current_status,
                ]);

                hooks()->do_action('lead_status_changed', [
                    'lead_id'    => $data['leadid'],
                    'old_status' => $old_status,
                    'new_status' => $current_status,
                ]);
            }
            $this->db->where('id', $data['leadid']);
            $this->db->update(db_prefix() . 'leads', [
                'last_status_change' => date('Y-m-d H:i:s'),
            ]);
        }
        if (isset($data['order'])) {
            foreach ($data['order'] as $order_data) {
                $this->db->where('id', $order_data[0]);
                $this->db->update(db_prefix() . 'leads', [
                    'leadorder' => $order_data[1],
                ]);
            }
        }
        if ($affectedRows > 0) {
            if ($_log_message == '') {
                return true;
            }
            $this->log_lead_activity($data['leadid'], $_log_message, false, $additional_data);

            return true;
        }

        return false;
    }

    public function lead_manger_activity_log($data = '')
    {
        $this->db->insert(db_prefix() . 'lead_manager_activity_log', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            return true;
        }
    }
    public function get_lead_manager_activity_log($id)
    {
        $sorting = hooks()->apply_filters('lead_activity_log_default_sort', 'ASC');

        $this->db->where('lead_id', $id);
        $this->db->order_by('date', $sorting);

        return $this->db->get(db_prefix() . 'lead_manager_activity_log')->result_array();
    }
    public function zoomMeetingDetails($id = '', $where = '')
    {
        if (is_array($where)) {
            $this->db->where($where);
        } else if (strlen($where) > 0) {
            $this->db->where($where);
        }
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'lead_manager_zoom_meeting')->row();
        } else {
            return $this->db->get(db_prefix() . 'lead_manager_zoom_meeting')->result_array();
        }
    }
    public function zoom_meeting_remarksDetails($id, $rel_type)
    {
        $this->db->where(['rel_type' => $rel_type, 'rel_id' => $id]);

        $this->db->order_by('date', 'DESC');

        return $this->db->get(db_prefix() . 'lead_manager_meeting_remark')->result();
    }
    public function update_last_contact($lead_id = '')
    {
        if ($lead_id) {
            $this->db->where('id', $lead_id);
            $this->db->update(db_prefix() . 'leads', [
                'lastcontact' => date('Y-m-d H:i:s'),
            ]);
            if ($this->db->affected_rows() > 0) {
                return true;
            }
        }
        return false;
    }

    public function save_zoom_meeting($data, $meeting_res_data)
    {
        if ($meeting_res_data) {
            $join_url = $meeting_res_data->join_url;
            $parts = explode("=", $join_url);
            $password = end($parts);
            $save_data = array(
                'name' => $data['user_name'],
                'staff_email' => $data['staff_email'],
                'staff_id' => !$data['is_client'] ? get_staff_user_id() : $data['staff_id'],
                'leadid' => $data['lead_id'],
                'staff_name' => $data['staff_name'],
                'email' => $data['user_email'],
                'meeting_id' => $meeting_res_data->id,
                'join_url' => $meeting_res_data->join_url,
                'start_url' => $meeting_res_data->start_url,
                'country' => $data['meeting_country'],
                'timezone' => $data['zoom_timezone'],
                'meeting_date' => $data['meeting_start_date'],
                'meeting_duration' => $data['meeting_duration'],
                'meeting_option' => implode(",", $data['meeting_option']),
                'meeting_agenda' => $data['meeting_agenda'],
                'meeting_description' => $data['meeting_description'],
                'status' => 'waiting',
                'is_client' => $data['is_client'],
                'password' => $password,
                'created_at' => date('Y-m-d H:i:s'),
            );
            $this->db->insert(db_prefix() . 'lead_manager_zoom_meeting', $save_data);
            $insertId = $this->db->insert_id();
            if ($insertId) {
                $this->send_email_to_user($insertId);
                $this->send_email_to_staff($insertId);
                return true;
            }
        }
        return false;
    }

    public function send_email_to_staff($id)
    {
        $this->db->where(db_prefix() . 'lead_manager_zoom_meeting.id', $id);
        $meeting_data = $this->db->get(db_prefix() . 'lead_manager_zoom_meeting')->row();
        $sent = lead_manager_send_mail_template('zoom_link_send_to_staff', $meeting_data);
        if ($sent) {
            return true;
        }
        return false;
    }
    public function send_email_to_user($id)
    {
        $this->db->where(db_prefix() . 'lead_manager_zoom_meeting.id', $id);
        $meeting_data = $this->db->get(db_prefix() . 'lead_manager_zoom_meeting')->row();
        $sent = false;
        if (isset($meeting_data) && $meeting_data->is_client == '1') {
            $sent = lead_manager_send_mail_template('zoom_link_send_to_customer', $meeting_data);
        } else if (isset($meeting_data) && $meeting_data->is_client == '0') {
            $sent = lead_manager_send_mail_template('zoom_link_send_to_lead', $meeting_data);
        }
        return $sent;
    }

    public function get_zoom_statuses()
    {
        $statuses = hooks()->apply_filters('before_get_zoom_statuses', [
            [
                'id'             => 0,
                'color'          => '#d9534f',
                'name'           => _l('End'),
                'order'          => 2,
                'filter_default' => true,
            ],
            [
                'id'             => 1,
                'color'          => '#f0ad4e',
                'name'           => _l('Waiting'),
                'order'          => 1,
                'filter_default' => true,
            ],

        ]);

        usort($statuses, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        return $statuses;
    }

    public function delete_zoom_meeting($id)
    {
        if ($id) {
            $this->db->where('id', $id);
            $this->db->delete(db_prefix() . 'lead_manager_zoom_meeting');
            return true;
        }
        return false;
    }
    public function update_meeting_status($data)
    {
        if ($data) {
            $this->db->where('id', $data['id']);
            $this->db->update(db_prefix() . 'lead_manager_zoom_meeting', [
                'status' => $data['status'],
            ]);
            return true;
        }
        return false;
    }

    public function get_staff_number()
    {
        $this->db->where(['relid' => get_staff_user_id(), 'fieldto' => 'staff']);
        $res2 = $this->db->get(db_prefix() . 'customfieldsvalues')->row();
        return $res2->value;
    }

    public function save_meeting_remark($data)
    {
        if ($data) {
            $saveData = array(
                'rel_id' => $data['meeting_id'],
                'remark' => $data['remark'],
                'date' => date('Y-m-d H:i:s'),
                'rel_type' => $data['rel_type'],
                'lm_follow_up_date' => isset($data['lm_follow_up']) ? $data['lm_follow_up'] : NULL
            );
            $this->db->insert(db_prefix() . 'lead_manager_meeting_remark', $saveData);
            if ($this->db->insert_id()) {
                if (isset($data) && isset($data['lm_follow_up']) && !empty($data['lm_follow_up'])) {
                    $this->db->where('id', $data['meeting_id']);
                    $this->db->update(db_prefix() . 'leads', array('lm_follow_up' => 1));
                } else {
                    $this->db->where('id', $data['meeting_id']);
                    $this->db->update(db_prefix() . 'leads', array('lm_follow_up' => 0));
                }
                $lead = $this->get($data['meeting_id']);
                $activity_data['type'] = 'remark';
                $activity_data['lead_id'] = $data['meeting_id'];
                $activity_data['date'] = date("Y-m-d H:i:s");
                $activity_data['description'] = $data['remark'];
                $activity_data['additional_data'] = NULL;
                $activity_data['staff_id'] = $lead ? $lead->assigned : 0;
                $activity_data['direction'] = 'outgoing';
                $activity_data['call_duration'] = NULL;
                $activity_data['is_client'] = !$lead ? 1 : 0;
                $response = $this->lead_manger_activity_log($activity_data);
                $this->log_lead_activity($data['meeting_id'], $data['remark'], false, NULL);
                return $response;
            }
        }
        return false;
    }

    function get_total_calls($data = array())
    {
        //$this->db->from(db_prefix() . 'lead_manager_activity_log');
        $this->db->select('direction,count("direction") as total');
        if (!is_admin()) {
            $this->db->where(['staff_id' => get_staff_user_id()]);
        }
        $this->db->where(['type' => 'audio_call']);
        if (isset($data) && !empty($data)) {
            if (isset($data['staff_id']) && !empty($data['staff_id'])) {
                $this->db->where(['staff_id' => $data['staff_id']]);
                $this->db->where('date >', 'DATE_ADD(NOW(), INTERVAL -' . $data['days'] . ' DAY)', FALSE);
            } else {
                $this->db->where('date >', 'DATE_ADD(NOW(), INTERVAL -' . $data['days'] . ' DAY)', FALSE);
            }
        } else {
            $this->db->where('date >', 'DATE_ADD(NOW(), INTERVAL -1 DAY)', FALSE);
        }
        $query = $this->db->group_by('direction')->get(db_prefix() . 'lead_manager_activity_log');

        $data =  $query->result();
        $result = null;
        if (isset($data) && !empty($data)) {
            foreach ($data as $obj) {
                $result[$obj->direction] = $obj->total ? $obj->total : 0;
            }
        }
        return $result;
    }

    public function get_total_sms($data = array())
    {
        $this->db->from(db_prefix() . 'lead_manager_activity_log');
        $this->db->select('count("id") as total');
        if (!is_admin()) {
            $this->db->where(['staff_id' => get_staff_user_id()]);
        }
        $this->db->where(['type' => 'sms']);
        if (isset($data) && !empty($data)) {
            if (isset($data['staff_id']) && !empty($data['staff_id'])) {
                $this->db->where(['staff_id' => $data['staff_id']]);
                $this->db->where('date >', 'DATE_ADD(NOW(), INTERVAL -' . $data['days'] . ' DAY)', FALSE);
            } else {
                $this->db->where('date >', 'DATE_ADD(NOW(), INTERVAL -' . $data['days'] . ' DAY)', FALSE);;
            }
        } else {
            $this->db->where('date >', 'DATE_ADD(NOW(), INTERVAL -1 DAY)', FALSE);
        }
        $query = $this->db->get();
        $data =  $query->result();
        $result = null;
        if (isset($data) && !empty($data)) {
            foreach ($data as $obj) {
                $result['sent'] = $obj->total ? $obj->total : 0;
            }
        }
        return $result['sent'];
    }
    public function get_total_missed_call($data = array())
    {
        $this->db->from(db_prefix() . 'lead_manager_missed_calls');
        $this->db->select('count("id") as total');
        if (!is_admin()) {
            $this->db->where(['staff_id' => get_staff_user_id()]);
        }
        if (isset($data) && !empty($data)) {
            if (isset($data['staff_id']) && !empty($data['staff_id'])) {
                $this->db->where(['staff_id' => $data['staff_id']]);
                $this->db->where('date >', 'DATE_ADD(NOW(), INTERVAL -' . $data['days'] . ' DAY)', FALSE);
            } else {
                $this->db->where('date >', 'DATE_ADD(NOW(), INTERVAL -' . $data['days'] . ' DAY)', FALSE);
            }
        } else {
            $this->db->where('date >', 'DATE_ADD(NOW(), INTERVAL -1 DAY)', FALSE);
        }
        $query = $this->db->get();
        $data =  $query->result();
        $result = null;
        if (isset($data) && !empty($data)) {
            foreach ($data as $obj) {
                $result['missed'] = $obj->total ? $obj->total : 0;
            }
        }
        return $result['missed'];
    }

    public function get_total_leads_converted($data = array())
    {
        $this->db->from(db_prefix() . 'leads');
        $this->db->select('count("id") as total');
        if (!is_admin()) {
            $this->db->where(['assigned' => get_staff_user_id()]);
        }
        $this->db->where(['converted_by_lead_manager' => TRUE]);

        if (isset($data) && !empty($data)) {
            if (isset($data['staff_id']) && !empty($data['staff_id'])) {
                $this->db->where(['assigned' => $data['staff_id']]);
                $this->db->where('last_status_change >', 'DATE_ADD(NOW(), INTERVAL -' . $data['days'] . ' DAY)', FALSE);
            } else {
                $this->db->where('last_status_change >', 'DATE_ADD(NOW(), INTERVAL -' . $data['days'] . ' DAY)', FALSE);
            }
        } else {
            $this->db->where('last_status_change >', 'DATE_ADD(NOW(), INTERVAL -1 DAY)', FALSE);
        }
        $query = $this->db->get();
        $data =  $query->result();
        $result = null;
        if (isset($data) && !empty($data)) {
            foreach ($data as $obj) {
                $result['converted'] = $obj->total ? $obj->total : 0;
            }
        }
        return $result['converted'];
    }

    public function get_total_calls_duration($data = array())
    {
        // $this->db->from(db_prefix() . 'lead_manager_activity_log');
        $this->db->select('direction,SUM(`call_duration`) as total');
        if (!is_admin()) {
            $this->db->where(['staff_id' => get_staff_user_id()]);
        }
        $this->db->where(['type' => 'audio_call']);
        if (isset($data) && !empty($data)) {
            if (isset($data['staff_id']) && !empty($data['staff_id'])) {
                $this->db->where(['staff_id' => $data['staff_id']]);
                $this->db->where('date >', 'DATE_ADD(NOW(), INTERVAL -' . $data['days'] . ' DAY)', FALSE);
            } else {
                $this->db->where('date >', 'DATE_ADD(NOW(), INTERVAL -' . $data['days'] . ' DAY)', FALSE);
            }
        } else {
            $this->db->where('date >', 'DATE_ADD(NOW(), INTERVAL -1 DAY)', FALSE);
        }
        $query = $this->db->group_by('direction')->get(db_prefix() . 'lead_manager_activity_log');
        $data =  $query->result();
        $result = null;
        if (isset($data) && !empty($data)) {
            foreach ($data as $obj) {
                $result[$obj->direction] = $obj->total ? gmdate("H:i:s", $obj->total) : '00:00:00';
            }
        }
        return $result;
    }
    public function get_total_zoom_sheduled($data = array())
    {
        $this->db->from(db_prefix() . 'lead_manager_zoom_meeting');
        $this->db->select('status,count("id") as total');
        if (!is_admin()) {
            $this->db->where(['staff_id' => get_staff_user_id()]);
        }

        if (isset($data) && !empty($data)) {
            if (isset($data['staff_id']) && !empty($data['staff_id'])) {
                $this->db->where(['staff_id' => $data['staff_id']]);
                $this->db->where('meeting_date >', 'DATE_ADD(NOW(), INTERVAL -' . $data['days'] . ' DAY)', FALSE);
            } else {
                $this->db->where('meeting_date >', 'DATE_ADD(NOW(), INTERVAL -' . $data['days'] . ' DAY)', FALSE);
            }
        } else {
            $this->db->where('meeting_date >', 'DATE_ADD(NOW(), INTERVAL -1 DAY)', FALSE);
        }

        $query = $this->db->group_by('status')->get();
        $data =  $query->result();
        $result['waiting'] = 0;
        $result['end'] = 0;
        if (isset($data) && !empty($data)) {
            foreach ($data as $obj) {
                if ($obj->status == 'waiting') {
                    $result['waiting'] = $obj->total ? $obj->total : 0;
                } elseif ($obj->status == 'end') {
                    $result['end'] = $obj->total ? $obj->total : 0;
                }
            }
        }
        return $result;
    }
    public function get_lead_attachments($id = '', $attachment_id = '', $where = [])
    {
        $this->db->where($where);
        $idIsHash = !is_numeric($attachment_id) && strlen($attachment_id) == 32;
        if (is_numeric($attachment_id) || $idIsHash) {
            $this->db->where($idIsHash ? 'attachment_key' : 'id', $attachment_id);

            return $this->db->get(db_prefix() . 'files')->row();
        }
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'lead');
        $this->db->order_by('dateadded', 'DESC');

        return $this->db->get(db_prefix() . 'files')->result_array();
    }
    public function log_lead_activity($id, $description, $integration = false, $additional_data = '')
    {
        $log = [
            'date'            => date('Y-m-d H:i:s'),
            'description'     => $description,
            'leadid'          => $id,
            'staffid'         => get_staff_user_id(),
            'additional_data' => $additional_data,
            'full_name'       => get_staff_full_name(get_staff_user_id()),
        ];
        if ($integration == true) {
            $log['staffid']   = 0;
            $log['full_name'] = '[CRON]';
        }

        $this->db->insert(db_prefix() . 'lead_activity_log', $log);

        return $this->db->insert_id();
    }
    public function get_follow_up_date($leadId)
    {
        $this->db->where(['rel_id' => $leadId]);
        $this->db->limit(1);
        $this->db->order_by('date', 'DESC');
        $this->db->select('lm_follow_up_date');
        return $this->db->get(db_prefix() . 'lead_manager_meeting_remark')->row();
    }
    public function delete($id)
    {
        $affectedRows = 0;

        hooks()->do_action('before_lead_manager_lead_deleted', $id);

        $lead = $this->get($id);

        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'leads');
        if ($this->db->affected_rows() > 0) {
            log_activity('Lead Deleted [Deleted by: ' . get_staff_full_name() . ', ID: ' . $id . ']');

            $attachments = $this->get_lead_attachments($id);
            foreach ($attachments as $attachment) {
                $this->delete_lead_attachment($attachment['id']);
            }

            // Delete the custom field values
            $this->db->where('relid', $id);
            $this->db->where('fieldto', 'leads');
            $this->db->delete(db_prefix() . 'customfieldsvalues');

            $this->db->where('leadid', $id);
            $this->db->delete(db_prefix() . 'lead_activity_log');

            $this->db->where('leadid', $id);
            $this->db->delete(db_prefix() . 'lead_integration_emails');

            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'lead');
            $this->db->delete(db_prefix() . 'notes');

            $this->db->where('rel_type', 'lead');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'reminders');

            $this->db->where('rel_type', 'lead');
            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'taggables');

            $this->load->model('proposals_model');
            $this->load->model('tasks_model');
            $this->db->where('rel_id', $id);
            $this->db->where('rel_type', 'lead');

            //lead_manager deleting

            $this->db->where('lead_id', $id);
            $this->db->delete(db_prefix() . 'lead_manager_activity_log');

            $this->db->where('rel_id', $id);
            $this->db->delete(db_prefix() . 'lead_manager_meeting_remark');

            $this->db->where('leadid', $id);
            $this->db->delete(db_prefix() . 'lead_manager_zoom_meeting');

            $this->db->where(['from_id' => $id, 'is_client' => 0]);
            $this->db->delete(db_prefix() . 'lead_manager_conversation');

            $this->db->where(['toid' => $id, 'is_client' => 0]);
            $this->db->delete(db_prefix() . 'lead_manager_mailbox');

            $this->db->where(['from_id' => $id, 'is_client' => 0]);
            $this->db->delete(db_prefix() . 'lead_manager_whatsapp');

            $proposals = $this->db->get(db_prefix() . 'proposals')->result_array();

            foreach ($proposals as $proposal) {
                $this->proposals_model->delete($proposal['id']);
            }

            // Get related tasks
            $this->db->where('rel_type', 'lead');
            $this->db->where('rel_id', $id);
            $tasks = $this->db->get(db_prefix() . 'tasks')->result_array();
            foreach ($tasks as $task) {
                $this->tasks_model->delete_task($task['id']);
            }

            if (is_gdpr()) {
                $this->db->where('(description LIKE "%' . $lead->email . '%" OR description LIKE "%' . $lead->name . '%" OR description LIKE "%' . $lead->phonenumber . '%")');
                $this->db->delete(db_prefix() . 'activity_log');
            }

            $affectedRows++;
        }
        if ($affectedRows > 0) {
            return true;
        }

        return false;
    }
    public function delete_lead_attachment($id)
    {
        $attachment = $this->get_lead_attachments('', $id);
        $deleted    = false;

        if ($attachment) {
            if (empty($attachment->external)) {
                unlink(get_upload_path_by_type('lead') . $attachment->rel_id . '/' . $attachment->file_name);
            }
            $this->db->where('id', $attachment->id);
            $this->db->delete(db_prefix() . 'files');
            if ($this->db->affected_rows() > 0) {
                $deleted = true;
                log_activity('Lead Attachment Deleted [ID: ' . $attachment->rel_id . ']');
            }

            if (is_dir(get_upload_path_by_type('lead') . $attachment->rel_id)) {
                // Check if no attachments left, so we can delete the folder also
                $other_attachments = list_files(get_upload_path_by_type('lead') . $attachment->rel_id);
                if (count($other_attachments) == 0) {
                    // okey only index.html so we can delete the folder also
                    delete_dir(get_upload_path_by_type('lead') . $attachment->rel_id);
                }
            }
        }

        return $deleted;
    }
    public function get_form($where)
    {
        $this->db->where($where);

        return $this->db->get(db_prefix() . 'web_to_lead')->row();
    }
    public function create_conversation($response, $data = [])
    {
        $response = json_decode($response);
        $insert = [];
        $to_id = $data['lead_id'];
        $insert['msg_service_id'] = $response->msg_service_id;
        $insert['msg_sid'] = $response->sid;
        $insert['from_number'] = get_staff_own_twilio_number();
        $insert['from_id'] = get_staff_user_id();
        $insert['to_number'] = $response->to;
        $insert['to_id'] = $to_id;
        $insert['sms_direction'] = 'outgoing';
        $insert['sms_status'] = $response->status;
        $insert['sms_body'] = $response->body;
        $insert['api_response'] = json_encode($response);
        $insert['is_client'] = $data['is_client'];
        $insert['is_read'] = 'yes';
        $insert['sms_date'] = $data['date'];
        $this->db->insert(db_prefix() . 'lead_manager_conversation', $insert);
        return $this->db->insert_id();
    }
    public function get_conversation($lead_id, $is_client)
    {
        if (is_numeric($lead_id)) {
            $staff_id = get_staff_user_id();
            $this->db->where(['from_id' => $lead_id, 'to_id' => $staff_id, 'sms_direction' => 'incoming', 'is_client' => $is_client])->update(db_prefix() . "lead_manager_conversation", ['is_read' => 'yes']);
            $query = $this->db->query("SELECT * FROM " . db_prefix() . "lead_manager_conversation WHERE ((to_id=" . $lead_id . " AND from_id=" . $staff_id . ") OR (to_id=" . $staff_id . " AND from_id=" . $lead_id . ")) AND is_client=" . $is_client);
            return $query->result_array();
        }
    }
    public function get_leads_years()
    {
        return $this->db->query('SELECT DISTINCT(YEAR(dateadded)) as year FROM ' . db_prefix() . 'leads')->result_array();
    }
    public function get_last_incoming_conversation($lead_id, $last_sms_id, $is_client)
    {
        $result = '';
        $staff_id = get_staff_user_id();
        $this->db->where(['to_id' => $staff_id, 'from_id' => $lead_id, 'sms_direction' => 'incoming', 'is_client' => $is_client, 'id <=' => $last_sms_id]);
        $this->db->update(db_prefix() . 'lead_manager_conversation', ['is_read' => 'yes']);
        if (is_numeric($lead_id) && is_numeric($last_sms_id)) {
            $this->db->limit(1);
            $this->db->where(['to_id' => $staff_id, 'from_id' => $lead_id, 'id >' => $last_sms_id, 'sms_direction' => 'incoming', 'is_client' => $is_client]);
            $query =  $this->db->get(db_prefix() . "lead_manager_conversation");
            $result = $query->row();
        }
        return $result;
    }

    public function get_incoming_notifications($ids, $is_client)
    {
        $ids = json_decode($ids);
        if (is_array($ids) && count($ids) > 0) {
            $ids = implode(',', $ids);
            $staff_id = get_staff_user_id();
            $query = $this->db->query("SELECT count(id) as total, from_id FROM " . db_prefix() . "lead_manager_conversation WHERE sms_direction='incoming' AND is_read='no' AND is_client='" . $is_client . "' AND from_id in(" . $ids . ") GROUP BY from_id");
            return $query->result();
        }
    }
    public function get_mail_box_configuration($staff_id = '')
    {
        if ($this->db->table_exists(db_prefix() . 'lead_manager_mailbox_settings')) {
            if (is_numeric($staff_id)) {
                return $this->db->get_where(db_prefix() . 'lead_manager_mailbox_settings', ['staff_id' => $staff_id])->row();
            }
            return $this->db->get(db_prefix() . 'lead_manager_mailbox_settings')->result_array();
        }
    }
    public function update_mail_box_configuration($data, $staff_id = '')
    {
        $data['is_smtp'] = $data['settings']['is_smtp'];
        $data['is_imap'] = $data['settings']['is_imap'];
        $data['smtp_password'] = $this->encryption->encrypt($data['smtp_password']);
        unset($data['settings']);
        $response = array('status' => 0, 'responseText' => _l('lm_alert_failed_to_update'));
        if (!is_numeric($staff_id)) {
            $staff_id = get_staff_user_id();
        }
        $saved_config = $this->db->get_where(db_prefix() . 'lead_manager_mailbox_settings', ['staff_id' => $staff_id])->row();
        if (total_rows(db_prefix() . 'lead_manager_mailbox_settings', ['staff_id' => $staff_id]) > 0) {
            if (isset($data['csrf_token_name'])) {
                unset($data['csrf_token_name']);
            }
            $this->db->where(['staff_id' => $staff_id]);
            $this->db->update(db_prefix() . 'lead_manager_mailbox_settings', $data);
            if ($this->db->affected_rows()) {
                if ($saved_config->imap_user != $data['imap_user']) {
                    $this->imap_credential_changed_event($staff_id);
                }
                if ($saved_config->smtp_user != $data['imap_user']) {
                    $this->smtp_credential_changed_event($staff_id);
                }
                $response['status'] = 1;
                $response['responseText'] = _l('lm_alert_success_to_update');
            } else {
                $response['responseText'] = _l('lm_alert_no_row_effected');
            }
        } else {
            $data['staff_id'] = $staff_id;
            if (isset($data['csrf_token_name'])) {
                unset($data['csrf_token_name']);
            }
            if ($this->db->insert(db_prefix() . 'lead_manager_mailbox_settings', $data)) {
                $response['status'] = 1;
                $response['responseText'] = _l('lm_alert_success_to_update');
            }
        }
        return $response;
    }
    public function send_simple_email_lm($mail_data)
    {
        $staff_id = get_staff_user_id();
        $this->load->config('lead_manager/email');
        $cnf = [
            'from_email' => $this->config->item('smtp_user'),
            'from_name'  => $this->config->item('smtp_fromname'),
            'email'      => $mail_data['to_email'],
            'subject'    => $mail_data['subject'],
            'message'    => $mail_data['message'],
        ];
        $template           = new StdClass();
        $template->message  = get_option('email_header') . $cnf['message'] . get_option('email_footer');
        $template->fromname = $cnf['from_name'];
        $template->subject  = $cnf['subject'];

        $template = parse_email_template($template);

        $cnf['message']   = $template->message;
        $cnf['from_name'] = $template->fromname;
        $cnf['subject']   = $template->subject;
        $cnf['to_cc']   = $mail_data['to_cc'] ?? NULL;

        $cnf['message'] = check_for_links($cnf['message']);

        if (isset($cnf['prevent_sending']) && $cnf['prevent_sending'] == true) {
            $this->lm_clear_attachments();

            return false;
        }
        $this->email->clear(true);
        $this->email->set_newline(config_item('newline'));
        $this->email->from($cnf['from_email'], $cnf['from_name']);
        $this->email->to($cnf['email']);
        $bcc = '';
        if (isset($cnf['bcc'])) {
            $bcc = $cnf['bcc'];
            if (is_array($bcc)) {
                $bcc = implode(', ', $bcc);
            }
        }

        $systemBCC = get_option('bcc_emails');
        if ($systemBCC != '') {
            if ($bcc != '') {
                $bcc .= ', ' . $systemBCC;
            } else {
                $bcc .= $systemBCC;
            }
        }
        if ($bcc != '') {
            $this->email->bcc($bcc);
        }

        if (isset($cnf['cc'])) {
            $this->email->cc($cnf['cc']);
        }

        if (isset($cnf['reply_to'])) {
            $this->email->reply_to($cnf['reply_to']);
        }

        $this->email->subject($cnf['subject']);
        $this->email->message($cnf['message']);
        $this->email->set_alt_message(strip_html_tags($cnf['message'], '<br/>, <br>, <br />'));
        if (isset($this->attachment) && count($this->attachment) > 0) {
            $this->attachment = $this->attachment[0];
            foreach ($this->attachment as $attach) {
                if (!isset($attach['read'])) {
                    $this->email->attach($attach['attachment'], 'attachment', $attach['file_name'], $attach['filetype']);
                } else {
                    if (!isset($attach['file_name']) || (isset($attach['file_name']) && empty($attach['file_name']))) {
                        $attach['file_name'] = basename($attach['attachment']);
                    }
                    $this->email->attach($attach['attachment'], '', $attach['file_name']);
                }
            }
        }
        $this->lm_clear_attachments();
        if ($this->email->send()) {
            log_activity('Email sent to: ' . $cnf['email'] . ' Subject: ' . $cnf['subject']);
            return true;
        } else {

            return $this->email->print_debugger();
        }
        return false;
    }
    public function addSentMailBox($data)
    {
        $this->db->insert(db_prefix() . 'lead_manager_mailbox', $data);
        $insert_id = $this->db->insert_id();
        return  $insert_id;
    }
    public function insertMailboxAttachments($attachments, $mailbox_id, $staff_id)
    {
        if ($mailbox_id && isset($attachments) && count($attachments) > 0) {
            foreach ($attachments as $attachment) {
                $attachment['staff_id'] = $staff_id;
                $attachment['mailbox_id'] = $mailbox_id;
                $this->db->insert(db_prefix() . 'lead_manager_mailbox_attachments', $attachment);
            }
        }
    }
    public function handleLeadManagerMailboxImapAttachments($message, $mailboxid, $staff_id)
    {
        $path = LEAD_MANAGER_MAILBOX_FOLDER . '/' . $mailboxid . '/';
        if (!file_exists($path)) {
            mkdir($path, 0755);
            file_put_contents($path . 'index.html', '');
        }
        foreach ($message->getAttachments() as $attachment) {
            $file_name = unique_filename($path, $attachment->getFilename());
            $path      = $path . $file_name;
            if (file_put_contents(
                $path,
                $attachment->getDecodedContent()
            )) {
                $attachments['staff_id'] = $staff_id;
                $attachments['mailbox_id'] = $mailboxid;
                $attachments['file_name'] = $file_name;
                $attachments['filetype'] = get_mime_by_extension($attachment->getFilename());
                $attachments['staff_id'] = get_staff_user_id();
                $this->db->insert(db_prefix() . 'lead_manager_mailbox_attachments', $attachments);
            }
        }
    }

    public function view_mail_box_email($id)
    {
        return $this->db->get_where(db_prefix() . 'lead_manager_mailbox', [db_prefix() . 'lead_manager_mailbox.id' => $id])->row();
    }
    public function get_mail_box_email_attachments($id)
    {
        return $this->db->get_where(db_prefix() . 'lead_manager_mailbox_attachments', [db_prefix() . 'lead_manager_mailbox_attachments.mailbox_id' => $id])->result_array();
    }
    public function mailbox_mark_as_bulk($data)
    {
        $ids = $data['ids'];
        unset($data['action']);
        unset($data['ids']);
        $this->db->where_in('id', $ids);
        $this->db->update(db_prefix() . 'lead_manager_mailbox', $data);
        return $this->db->affected_rows();
    }
    public function mailbox_mark_as_bulk_delete($data)
    {
        $ids = $data['ids'];
        unset($data['action']);
        unset($data['ids']);
        $this->db->where_in('id', $ids);
        $result = $this->db->get(db_prefix() . 'lead_manager_mailbox')->result();
        if (isset($result) && !empty($result)) {
            foreach ($result as $mail) {
                if ($mail->status == 'trash') {
                    if ($mail->is_attachment) {
                        delete_dir(LEAD_MANAGER_MAILBOX_FOLDER . $mail->id);
                    }
                    $this->db->where('id', $mail->id);
                    $this->db->delete(db_prefix() . 'lead_manager_mailbox');
                } else {
                    $this->db->where('id', $mail->id);
                    $this->db->update(db_prefix() . 'lead_manager_mailbox', ['status' => 'trash']);
                }
            }
            return true;
        }
        return false;
    }
    public function get_mail_box_last_sequence($staff_id = '')
    {
        if (is_numeric($staff_id)) {
            $this->db->select('MAX(sequence_id) as sequence_id');
            $this->db->from(db_prefix() . 'lead_manager_mailbox');
            $this->db->where(['staffid' => $staff_id]);
            $this->db->group_by('staffid');
            $data = $this->db->get()->row();
            if (isset($data) && !empty($data)) {
                return $data->sequence_id;
            } else {
                return 0;
            }
        }
    }
    public function view_mail_box_email_next($id, $staff_id)
    {
        $this->db->select('id');
        $this->db->where('id=(SELECT min(id) FROM ' . db_prefix() . 'lead_manager_mailbox WHERE id > ' . $id . ' AND staffid =' . $staff_id . ')');
        $this->db->from(db_prefix() . 'lead_manager_mailbox');
        return $this->db->get()->row();
    }
    public function view_mail_box_email_prev($id, $staff_id)
    {
        $this->db->select('id');
        $this->db->where('id=(SELECT max(id) FROM ' . db_prefix() . 'lead_manager_mailbox WHERE id < ' . $id . ' AND staffid =' . $staff_id . ')');
        $this->db->from(db_prefix() . 'lead_manager_mailbox');
        return $this->db->get()->row();
    }
    public function update_mailbox_data($data, $id)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'lead_manager_mailbox', $data);
        if ($this->db->affected_rows() > 0) {
            return true;
        }
        return false;
    }
    public function add_attachment($attachment)
    {
        $this->attachment[] = $attachment;
    }
    public function imap_credential_changed_event($staff_id)
    {
        $emails = $this->db->get_where(db_prefix() . 'lead_manager_mailbox', ['staffid' => $staff_id, 'direction' => 'inbound'])->result();
        if (isset($emails) && !empty($emails)) {
            foreach ($emails as $mail) {
                $this->db->where('id', $mail->id);
                $this->db->delete(db_prefix() . 'lead_manager_mailbox');
                if ($this->db->affected_rows() > 0) {
                    update_option('lead_manager_imap_last_checked', null);
                    if (is_dir(LEAD_MANAGER_MAILBOX_FOLDER . '/' . $mail->id . '/')) {
                        delete_dir(LEAD_MANAGER_MAILBOX_FOLDER . '/' . $mail->id . '/');
                    }
                }
            }
        }
    }
    public function smtp_credential_changed_event($staff_id)
    {
        $emails = $this->db->get_where(db_prefix() . 'lead_manager_mailbox', ['staffid' => $staff_id, 'direction' => 'outbound'])->result();
        if (isset($emails) && !empty($emails)) {
            foreach ($emails as $mail) {
                $this->db->where('id', $mail->id);
                $this->db->delete(db_prefix() . 'lead_manager_mailbox');
                if ($this->db->affected_rows() > 0) {
                    if (is_dir(LEAD_MANAGER_MAILBOX_FOLDER . '/' . $mail->id . '/')) {
                        delete_dir(LEAD_MANAGER_MAILBOX_FOLDER . '/' . $mail->id . '/');
                    }
                }
            }
        }
    }
    public function is_lead_manager_active()
    {
        $result = $this->db->get_where(db_prefix() . 'modules', ['module_name' => 'lead_manager'])->row();
        if (isset($result) && !empty($result)) {
            return true;
        }
    }
    public function createConfrenceCallBack($data)
    {
        $this->db->insert(db_prefix() . 'lead_manager_conference', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            return $insert_id;
        }
    }
    public function get_conference($FriendlyName = '', $where = [])
    {
        $this->db->where($where);
        if ($FriendlyName) {
            $this->db->where(['FriendlyName' => $FriendlyName]);
            $conference = $this->db->get(db_prefix() . 'lead_manager_conference')->row();
            return $conference;
        }
        return $this->db->get(db_prefix() . 'lead_manager_conference')->result_array();
    }
    public function get_contact($id = '', $where = [])
    {
        if (count($where) > 0) {
            $this->db->where($where);
        }
        if (is_numeric($id)) {
            $this->db->where('id', $id);
        }
        return $this->db->get(db_prefix() . 'contacts')->row();
    }
    public function get_client($id = '', $where = [])
    {
        if (count($where) > 0) {
            $this->db->where($where);
        }
        if (is_numeric($id)) {
            $this->db->where('id', $id);
        }
        return $this->db->get(db_prefix() . 'clients')->row();
    }
    public function add_lead($data)
    {
        if (isset($data['custom_contact_date']) || isset($data['custom_contact_date'])) {
            if (isset($data['contacted_today'])) {
                $data['lastcontact'] = date('Y-m-d H:i:s');
                unset($data['contacted_today']);
            } else {
                $data['lastcontact'] = to_sql_date($data['custom_contact_date'], true);
            }
        }

        if (isset($data['is_public']) && ($data['is_public'] == 1 || $data['is_public'] === 'on')) {
            $data['is_public'] = 1;
        } else {
            $data['is_public'] = 0;
        }

        if (!isset($data['country']) || isset($data['country']) && $data['country'] == '') {
            $data['country'] = 0;
        }

        if (isset($data['custom_contact_date'])) {
            unset($data['custom_contact_date']);
        }

        $data['description'] = nl2br($data['description']);
        $data['dateadded']   = date('Y-m-d H:i:s');
        $data['addedfrom']   = $data['addedfrom'];

        $data = hooks()->apply_filters('before_lead_added', $data);

        $tags = '';
        if (isset($data['tags'])) {
            $tags = $data['tags'];
            unset($data['tags']);
        }

        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }

        $data['address'] = trim($data['address']);
        $data['address'] = nl2br($data['address']);

        $data['email'] = trim($data['email']);
        $this->db->insert(db_prefix() . 'leads', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Lead Added [ID: ' . $insert_id . ']');
            handle_tags_save($tags, $insert_id, 'lead');

            if (isset($custom_fields)) {
                handle_custom_fields_post($insert_id, $custom_fields);
            }
            hooks()->do_action('lead_created', $insert_id);

            return $insert_id;
        }

        return false;
    }

    public function get_zoom_meetings($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'lead_manager_zoom_meeting.id', $id);
            return $this->db->get(db_prefix() . 'lead_manager_zoom_meeting')->row();
        }
        return $this->db->get(db_prefix() . 'lead_manager_zoom_meeting')->result_array();
    }
    public function get_conversation_whatsapp($lead_id, $is_client)
    {
        if (is_numeric($lead_id)) {
            $staff_id = get_staff_user_id();
            $this->db->where(['from_id' => $lead_id, 'to_id' => $staff_id, 'sms_direction' => 'incoming', 'is_client' => $is_client])->update(db_prefix() . "lead_manager_whatsapp", ['is_read' => 'yes']);
            $query = $this->db->query("SELECT W.*, F.file_name, F.filetype, F.type FROM " . db_prefix() . "lead_manager_whatsapp as W  LEFT JOIN " . db_prefix() . "lead_manager_whatsapp_files as F ON F.id=W.is_files WHERE ((W.to_id=" . $lead_id . " AND W.from_id=" . $staff_id . ") OR (W.to_id=" . $staff_id . " AND W.from_id=" . $lead_id . ")) AND W.is_client=" . $is_client);
            return $query->result_array();
        }
    }
    public function count_unread_whatsapp_messages_lead($staff_id)
    {
        $query = $this->db->query("SELECT count(is_read) as unread, from_id, MAX(id) as last_sms_id FROM " . db_prefix() . "lead_manager_whatsapp WHERE (to_id=" . $staff_id . " AND sms_direction='incoming' AND is_client='0' AND is_read='no') GROUP BY from_id");
        return $query->result_array();
    }
    public function count_unread_whatsapp_messages_client($staff_id)
    {
        $query = $this->db->query("SELECT count(is_read) as unread, from_id, MAX(id) as last_sms_id FROM " . db_prefix() . "lead_manager_whatsapp WHERE (to_id=" . $staff_id . " AND sms_direction='incoming' AND is_client='1' AND is_read='no') GROUP BY from_id");
        return $query->result_array();
    }
    public function update_last_incoming_whatsapp($data)
    {
        $staff_id = get_staff_user_id();
        $this->db->where(['to_id' => $staff_id, 'from_id' => $data['lm_leadid'], 'sms_direction' => 'incoming', 'is_client' => $data['is_client'], 'id <=' => $data['last_sms_id']]);
        $this->db->update(db_prefix() . 'lead_manager_whatsapp', ['is_read' => 'yes']);
        return $this->db->affected_rows();
    }
    public function create_conversation_whatsaap($msg_response, $data = [])
    {
        $insert = [];
        $curr_time = date("Y-m-d H:i:s");
        $to_id = $data['lead_id'];
        $insert['msg_sid'] = $msg_response['sid'];
        $insert['from_number'] = str_replace('whatsapp:', '', $msg_response['from']);
        $insert['from_id'] = get_staff_user_id();
        $insert['to_number'] = str_replace('whatsapp:', '', $msg_response['to']);
        $insert['to_id'] = $to_id;
        $insert['sms_direction'] = 'outgoing';
        $insert['sms_status'] = $msg_response['status'];
        $insert['sms_body'] = $msg_response['body'];
        $insert['api_response'] = json_encode($msg_response);
        $insert['is_client'] = $data['is_client'];
        $insert['is_read'] = 'yes';
        $insert['sms_date'] = to_sql_date($curr_time, true);
        if (isset($data['media'])) {
            $insert['is_files'] = $data['media'];
        }
        $this->db->insert(db_prefix() . 'lead_manager_whatsapp', $insert);
        return $this->db->insert_id();
    }
    public function update_whatsapp_msg($data)
    {
        $this->db->where(['msg_sid' => $data['SmsSid']]);
        $this->db->update(db_prefix() . 'lead_manager_whatsapp', ['sms_status' => $data['MessageStatus']]);
        return $this->db->affected_rows();
    }
    public function total_unread_whatsapp_messages()
    {
        $query = $this->db->query("SELECT count(*) as unread FROM " . db_prefix() . "lead_manager_whatsapp WHERE to_id=" . get_staff_user_id() . " AND is_read='NO' AND sms_direction='incoming'");
        return $query->row()->unread;
    }
    public function get_total_unread_sms_by_staff($staff_id)
    {
        $query = $this->db->query("SELECT count(*) as unread FROM " . db_prefix() . "lead_manager_conversation WHERE to_id=" . $staff_id . " AND is_read='NO' AND sms_direction='incoming'");
        return $query->row()->unread;
    }
    public function count_unread_sms_lead($staff_id)
    {
        $query = $this->db->query("SELECT count(is_read) as unread, from_id, MAX(id) as last_sms_id FROM " . db_prefix() . "lead_manager_conversation WHERE (to_id=" . $staff_id . " AND sms_direction='incoming' AND is_client='0' AND is_read='no') GROUP BY from_id");
        return $query->result_array();
    }
    public function count_unread_sms_client($staff_id)
    {
        $query = $this->db->query("SELECT count(is_read) as unread, from_id, MAX(id) as last_sms_id FROM " . db_prefix() . "lead_manager_conversation WHERE (to_id=" . $staff_id . " AND sms_direction='incoming' AND is_client='1' AND is_read='no') GROUP BY from_id");
        return $query->result_array();
    }
    public function get_unread_sms_by_staff($staff_id)
    {
        $lead_unread = $this->count_unread_sms_lead($staff_id);
        $client_unread = $this->count_unread_sms_client($staff_id);
        return [
            'leads' => $lead_unread,
            'clients' => $client_unread
        ];
    }
    public function get_whatsapp_file($id = false)
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get(db_prefix() . 'lead_manager_whatsapp_files')->row();
        }
        return $this->db->get(db_prefix() . 'lead_manager_whatsapp_files')->result_array();
    }
    public function update_last_incoming_sms($data)
    {
        $staff_id = get_staff_user_id();
        $this->db->where(['to_id' => $staff_id, 'from_id' => $data['lm_leadid'], 'sms_direction' => 'incoming', 'is_client' => $data['is_client'], 'id <=' => $data['last_sms_id']]);
        $this->db->update(db_prefix() . 'lead_manager_conversation', ['is_read' => 'yes']);
        return $this->db->affected_rows();
    }
    public function get_whatsapp_msg_row($where)
    {
        $this->db->where($where);
        return $this->db->get(db_prefix() . 'lead_manager_whatsapp')->row();
    }
    public function get_whatsapp_templates($where = [])
    {
        if (count($where) > 0) {
            $this->db->where($where);
        }
        return $this->db->get(db_prefix() . 'lead_manager_whatsapp_templates')->row();
    }
    public function send_email($mailbox_id)
    {
        $sent = false;
        $mailbox = $this->view_mail_box_email($mailbox_id);
        if (isset($mailbox) && $mailbox->is_client == '1') {
            $sent = lead_manager_send_mail_template('email_send_to_customer', $mailbox);
        } else if (isset($mailbox) && $mailbox->is_client == '0') {
            $sent = lead_manager_send_mail_template('email_send_to_lead', $mailbox);
        }
        return $sent;
    }
    public function add_whatsapp_template($data)
    {
        $this->db->insert(db_prefix() . 'lead_manager_whatsapp_templates', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            if ($data['status'] == 'active') {
                $this->db->where('id !=' . $insert_id);
                $this->db->update(db_prefix() . 'lead_manager_whatsapp_templates', ['status' => 'inactive']);
            }
        }
        return $insert_id;
    }
    public function delete_whatsapp_template($id)
    {
        if ($id) {
            $this->db->where('id', $id);
            $this->db->delete(db_prefix() . 'lead_manager_whatsapp_templates');
            return true;
        }
        return false;
    }
    public function get_zoom_access_token($data)
    {
        $this->db->where(['expires_in >' => date('Y-m-d H:i:s')]);
        return $this->db->get(db_prefix() . 'lead_manager_zoom_meeting_access_token')->row_array();
    }
    public function save_zoom_access_token($data)
    {
        if ($data && isset($data['staffid'])) {
            $existingRecord = $this->db->get_where(db_prefix() . 'lead_manager_zoom_meeting_access_token',
                array('staff_id' => $data['staffid']))->row();
            if ($existingRecord) {
                $update_data = array(
                    'access_token' => $data['access_token'],
                    'token_type' => $data['token_type'],
                    'expires_in' => date("Y-m-d H:i:s", time() + $data['expires_in']),
                    'scope' => $data['scope'],
                    'updated_at' => date('Y-m-d H:i:s')
                );
                $this->db->where('staff_id', $data['staffid']);
                $this->db->update(db_prefix() . 'lead_manager_zoom_meeting_access_token', $update_data);
                return $update_data; 
            } else {
                $insert_data = array(
                    'staff_id' => $data['staffid'],
                    'access_token' => $data['access_token'],
                    'token_type' => $data['token_type'],
                    'expires_in' => date("Y-m-d H:i:s", time() + $data['expires_in']),
                    'scope' => $data['scope'],
                );
                $this->db->insert(db_prefix() . 'lead_manager_zoom_meeting_access_token', $insert_data);
                return $insert_data; 
            }
        }
        return false;
    }
    public function update_zoom_meeting($data,$meeting_id)
    {
        if ($data) {
            $save_data = array(
                'timezone' => $data['zoom_timezone'],
                'meeting_date' => $data['meeting_start_date'],
                'meeting_duration' => $data['meeting_duration'],
                'meeting_option' => implode(",", $data['meeting_option']),
                'meeting_agenda' => $data['meeting_agenda'],
                'meeting_description' => $data['meeting_description'],
                'status' => 'waiting',
                'created_at' => date('Y-m-d H:i:s'),
            );
            $this->db->where('meeting_id', $meeting_id);
            $this->db->update(db_prefix() . 'lead_manager_zoom_meeting', $save_data);
        }
        return true;
    }
}
