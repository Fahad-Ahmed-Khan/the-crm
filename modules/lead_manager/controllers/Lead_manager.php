<?php defined('BASEPATH') or exit('No direct script access allowed');
$check =  __dir__;
$str = preg_replace('/\W\w+\s*(\W*)$/', '$1', $check);
$str . '/third_party/twilio-web/src/Twilio/autoload.php';

use Twilio\Rest\Client;
use Twilio\Jwt\ClientToken;

class Lead_manager extends AdminController
{
    protected $pusher_options = array();
    protected $pusher;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('lead_manager_model');
        $this->load->library('app_modules');
        if(!$this->app_modules->is_active('lead_manager')){
            access_denied("Lead Manager");
        }
        \modules\lead_manager\core\Leadmanagerinit::check_url('lead_manager');
        $this->load->model('clients_model');
        $this->load->library('mails/lead_manager_mail_template');
        $this->load->library('sms/sms_twilio_lead_manager');
        $this->load->library('zoom/ZoomJwtApiWrapper', ['apiKey' => get_option('zoom_api_key'), 'apiSecret' => get_option('zoom_secret_key')], 'zoom_api');
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

    public function generateClientToken()
    {
        $staffid = get_staff_user_id();
        $client = new ClientToken(get_option('call_twilio_account_sid'), get_option('call_twilio_auth_token'));
        $client->allowClientOutgoing(get_option('call_twiml_app_sid'));
        $client->allowClientIncoming('support_agent_' . $staffid);
        $token = $client->generateToken();
        echo json_encode(['token' => $token]);
        die();
    }

    /* List all leads */
    public function index($id = '')
    {
        close_setup_menu();
        if (!is_staff_member()) {
            access_denied('Leads');
        }
        $data['staffs'] = $this->staff_model->get('', ['active' => 1]);
        if (is_gdpr() && get_option('gdpr_enable_consent_for_leads') == '1') {
            $this->load->model('gdpr_model');
            $data['consent_purposes'] = $this->gdpr_model->get_consent_purposes();
        }
        $data['summary']  = get_leads_summary();
        $data['statuses'] = $this->lead_manager_model->get_status();
        $data['sources']  = $this->lead_manager_model->get_source();
        $data['lm_follow_ups']  = [['id' => 0, 'name' => 'No'], ['id' => 1, 'name' => 'Yes']];
        $data['years']                 = $this->lead_manager_model->get_leads_years();
        $data['title']    = _l('lead_manager');
        $data['leadid'] = $id;
        $this->load->view('admin/leads/manage_leads', $data);
    }

    public function shedule_appointment($id = '')
    {
        if (!is_staff_member()) {
            access_denied('Leads');
        }
        $data['staff'] = $this->staff_model->get('', ['active' => 1]);
        $data['title']    = _l('lead_manager_zoom_meetings');
        $data['leadid'] = $id;
        $this->load->view('admin/leads/manage_zoom_appointment', $data);
    }
    public function table()
    {
        if (!is_staff_member()) {
            ajax_access_denied();
        }
        $this->app->get_table_data(module_views_path('lead_manager', 'table'));
    }
    public function zoom_appointment_table()
    {
        if (!is_staff_member()) {
            ajax_access_denied();
        }
        $this->app->get_table_data(module_views_path('lead_manager', 'zoom_appointment_table'));
    }

    public function export($id)
    {
        if (is_admin()) {
            $this->load->library('gdpr/gdpr_lead');
            $this->gdpr_lead->export($id);
        }
    }

    /* Delete lead from database */
    public function delete($id)
    {
        if (!$id) {
            redirect(admin_url('leads'));
        }

        if (!is_lead_creator($id) && !has_permission('leads', '', 'delete')) {
            access_denied('Delte Lead');
        }
        $this->load->model('leads_model');
        $response = $this->leads_model->delete($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('lead_lowercase')));
        } elseif ($response === true) {
            set_alert('success', _l('deleted', _l('lead')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('lead_lowercase')));
        }

        $ref = $_SERVER['HTTP_REFERER'];

        if (!$ref || strpos($ref, 'index/' . $id) !== false) {
            redirect(admin_url('leads'));
        }

        redirect($ref);
    }

    public function update_lead_status()
    {
        if ($this->input->post() && $this->input->is_ajax_request()) {
            $this->lead_manager_model->update_lead_status($this->input->post());
        }
    }

    public function activity_log()
    {
        $view = '';
        $id = $this->input->get('id');
        $data['activity_log']         = $this->lead_manager_model->get_lead_manager_activity_log($id);
        $leadWhere = (has_permission('leads', '', 'view') ? [] : '(assigned = ' . get_staff_user_id() . ' OR addedfrom=' . get_staff_user_id() . ' OR is_public=1)');
        if (!is_null($this->input->get('is_client'))) {
            $data['lead'] = $this->clients_model->get($id);
            $data['is_client'] = 1;
            $view = $this->load->view('lead_manager/activity_log_client', $data, true);
        } else {
            $data['lead'] = $this->lead_manager_model->get($id, $leadWhere);
            $view = $this->load->view('lead_manager/avtivity_log_modal', $data, true);
        }
        echo $view;
        exit();
    }
    public function send_sms_modal()
    {
        $id = $this->input->get('id');
        $clientid = $this->input->get('clientid');
        $data = [];
        if (!is_null($this->input->get('is_client'))) {
            $data['lead'] = $this->clients_model->get_contact($id);
            if (!isset($data['lead']) && empty($data['lead'])) {
                $data['lead'] = $this->clients_model->get($clientid);
            }
            $data['is_client'] = 1;
        } else {
            $leadWhere = (has_permission('leads', '', 'view') ? [] : '(assigned = ' . get_staff_user_id() . ' OR addedfrom=' . get_staff_user_id() . ' OR is_public=1)');
            $data['lead'] = $this->lead_manager_model->get($id, $leadWhere);
        }
        $view = $this->load->view('lead_manager/send_sms_modal', $data, true);
        echo $view;
        exit();
    }
    public function send_zoom_link_modal()
    {
        $id = $this->input->get('id');
        $data = [];
        if (!is_null($this->input->get('is_client'))) {
            $data['lead'] = $this->clients_model->get_contact($id);
            $data['is_client'] = 1;
            $data['staffs'] = $this->staff_model->get('', ['active' => 1]);
        } else {
            $data['is_client'] = 0;
            $leadWhere = (has_permission('leads', '', 'view') ? [] : '(assigned = ' . get_staff_user_id() . ' OR addedfrom=' . get_staff_user_id() . ' OR is_public=1)');
            $data['lead'] = $this->lead_manager_model->get($id, $leadWhere);
        }
        if (isset($data['lead']->email) && !empty($data['lead']->email)) {
            $view = $this->load->view('lead_manager/send_zoom_link_modal', $data, true);
            echo $view;
            exit();
        } else {
            echo 'email not found!';
            exit;
        }
    }
    public function send_sms()
    {
        $activeSmsGateway = $this->app_sms->get_active_gateway();
        $data = array();
        $lead = '';
        $todayDate = date("Y-m-d H:i:s");
        if (isset($activeSmsGateway) && !empty($activeSmsGateway)) {
            $post_data = $this->input->post();
            if (isset($post_data['is_client']) && $post_data['is_client'] == 'client') {
                $lead = $this->clients_model->get_contact($post_data['lm_leadid']);
                if (!isset($lead) && empty($lead)) {
                    $lead = $this->clients_model->get($post_data['lm_leadid']);
                }
            }
            if (isset($post_data['is_client']) && $post_data['is_client'] == 'lead') {
                $lead = $this->lead_manager_model->get($post_data['lm_leadid']);
            }
            $phoneNumber = $lead->phonenumber;
            app_init_sms_gateways();
            $retval = $this->sms_twilio_lead_manager->send(
                $phoneNumber,
                clear_textarea_breaks(nl2br($this->input->post('message')))
            );
            $staff_id = get_staff_user_id();
            $response = ['success' => false];
            if (isset($GLOBALS['sms_error'])) {
                $response['error'] = $GLOBALS['sms_error'];
            } else {
                $response['success'] = true;
                $data['type'] = 'sms';
                $data['lead_id'] = $post_data['lm_leadid'];
                $data['date'] = to_sql_date($todayDate, true);
                $data['description'] = $post_data['message'];
                $data['additional_data'] = null;
                $data['staff_id'] = isset($post_data['is_client']) ? $staff_id : $lead->assigned;
                $data['direction'] = 'outgoing';
                $data['is_client'] = $post_data['is_client'] == 'client' ? 1 : 0;
                $response_activity = $this->lead_manager_model->lead_manger_activity_log($data);
                if ($post_data['is_client'] != 'client') {
                    $this->lead_manager_model->update_last_contact($post_data['lm_leadid']);
                    $response['profile_image'] = base_url('assets/images/user-placeholder.jpg');
                } else {
                    $primary_contact_id = get_primary_contact_user_id($post_data['lm_leadid']);
                    if (isset($primary_contact_id) && !empty($primary_contact_id)) {
                        $response['profile_image'] = contact_profile_image_url($primary_contact_id);
                    }
                }
                $response['sms_id'] = $this->lead_manager_model->create_conversation($retval, $data);
                $response['time'] = _dt($data['date']);
                $response['sms_status'] = 'queued';
            }
            echo json_encode($response);
            die;
        } else {
            $response['error'] = "Not sent. Gatway is undefined/inactive!";
            echo json_encode($response);
            die;
        }
    }
    public function bulk_action()
    {
        if (!is_staff_member()) {
            ajax_access_denied();
        }
        if ($this->input->post()) {
            $ids                   = $this->input->post('ids');
            $message                = $this->input->post('message');
            $failedData = array();
            if (is_array($ids)) {
                foreach ($ids as $id) {
                    $activeSmsGateway = $this->app_sms->get_active_gateway();
                    $data = array();
                    if ($message) {
                        $lead = $this->lead_manager_model->get($id);
                        $phoneNumber = $lead->phonenumber;
                        app_init_sms_gateways();
                        $retval = $this->{'sms_' . $activeSmsGateway['id']}->send(
                            $phoneNumber,
                            clear_textarea_breaks(nl2br($message))
                        );
                        $response = ['success' => false];
                        if (isset($GLOBALS['sms_error'])) {
                            $failedData[$id] = $GLOBALS['sms_error'];
                        } else {
                            $data['type'] = 'sms';
                            $data['is_audio_call_recorded'] = 0;
                            $data['lead_id'] = $id;
                            $data['date'] = date("Y-m-d H:i:s");
                            $data['description'] = $message;
                            $data['additional_data'] = null;
                            $data['staff_id'] = $lead->assigned;
                            $data['direction'] = 'outgoing';
                            $response_activity = $this->lead_manager_model->lead_manger_activity_log($data);
                            $this->lead_manager_model->update_last_contact($id);
                        }
                    }
                }
                echo json_encode([
                    'success'  => _l('lead_manager_bulk_sms_sent'),
                    'message'  => json_encode($failedData)
                ]);
                die;
            } else {
                set_alert('danger', _l('lead_manager_bulk_sms_empty_array'));
            }
        }
    }
    public function dashboard()
    {
        if (!$this->input->is_ajax_request()) {
            $data['audio_calls'] = $this->lead_manager_model->get_total_calls();
            $data['audio_calls_duration'] = $this->lead_manager_model->get_total_calls_duration();
            $data['sms'] = $this->lead_manager_model->get_total_sms();
            $data['missed_call'] = $this->lead_manager_model->get_total_missed_call();
            $data['leads_converted'] = $this->lead_manager_model->get_total_leads_converted();
            $data['zoom'] = $this->lead_manager_model->get_total_zoom_sheduled();
            $data['twilio'] = $this->active_twilio_account();
            $data['staff'] = $this->staff_model->get('', ['active' => 1]);
            add_calendar_assets();
            $this->load->view('admin/leads/dashboard', $data);
        } else {
            $staff_id = '';
            if ($this->input->get('staff_id')) {
                $staff_id = $this->input->get('staff_id');
            }
            $request_data['staff_id'] = $staff_id;
            $request_data['days'] = $this->input->get('days');
            $data['audio_calls'] = $this->lead_manager_model->get_total_calls($request_data);
            $data['audio_calls_duration'] = $this->lead_manager_model->get_total_calls_duration($request_data);
            $data['sms'] = $this->lead_manager_model->get_total_sms($request_data);
            $data['missed_call'] = $this->lead_manager_model->get_total_missed_call($request_data);
            $data['leads_converted'] = $this->lead_manager_model->get_total_leads_converted($request_data);
            $data['zoom'] = $this->lead_manager_model->get_total_zoom_sheduled($request_data);
            $data['staff'] = $this->staff_model->get($staff_id, ['active' => 1]);
            $data['twilio'] = $this->active_twilio_account();
            $this->load->view('admin/leads/dashboard-ajax', $data);
        }
    }

    public function active_twilio_account()
    {
        $response = array('numbers' => 0, 'balance' => 0);
        if (get_option('call_twilio_active')) {
            $sid  = get_option('call_twilio_account_sid');
            $token  = get_option('call_twilio_auth_token');
            try {
                $twilio = new Client($sid, $token);
                $incomingPhoneNumbers = $twilio->incomingPhoneNumbers
                    ->read([]);
                $response['numbers'] = count($incomingPhoneNumbers);
                $account = $twilio->api->v2010->accounts($sid)
                    ->fetch();
                $response['balance'] = $this->active_twilio_account_curl($account->subresourceUris['balance']);
            } catch (Exception $e) {
                set_alert('warning', 'Twilio ' . $e->getMessage());
            }
        }
        return $response;
    }

    public function active_twilio_account_curl($url)
    {
        $sid  = get_option('call_twilio_account_sid');
        $token  = get_option('call_twilio_auth_token');
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_USERPWD, $sid . ":" . $token);
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.twilio.com/' . $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $data = json_decode($response);
        return $data->balance;
    }

    public function get_convert_data($id)
    {
        $this->load->model('leads_model');
        if (!is_staff_member() || !$this->leads_model->staff_can_access_lead($id)) {
            ajax_access_denied();
        }
        if (is_gdpr() && get_option('gdpr_enable_consent_for_contacts') == '1') {
            $this->load->model('gdpr_model');
            $data['purposes'] = $this->gdpr_model->get_consent_purposes($id, 'lead');
        }
        $data['lead'] = $this->lead_manager_model->get($id);
        $this->load->view('admin/leads/convert_to_customer', $data);
    }
    public function convert_to_customer()
    {
        if (!is_staff_member()) {
            access_denied('Lead Convert to Customer');
        }
        $this->load->model('leads_model');
        if ($this->input->post()) {
            $default_country  = get_option('customer_default_country');
            $data             = $this->input->post();
            $data['password'] = $this->input->post('password', false);

            $original_lead_email = $data['original_lead_email'];
            unset($data['original_lead_email']);
            unset($data['converted_by_lead_manager']);

            if (isset($data['transfer_notes'])) {
                $notes = $this->misc_model->get_notes($data['leadid'], 'lead');
                unset($data['transfer_notes']);
            }

            if (isset($data['transfer_consent'])) {
                $this->load->model('gdpr_model');
                $consents = $this->gdpr_model->get_consents(['lead_id' => $data['leadid']]);
                unset($data['transfer_consent']);
            }

            if (isset($data['merge_db_fields'])) {
                $merge_db_fields = $data['merge_db_fields'];
                unset($data['merge_db_fields']);
            }

            if (isset($data['merge_db_contact_fields'])) {
                $merge_db_contact_fields = $data['merge_db_contact_fields'];
                unset($data['merge_db_contact_fields']);
            }

            if (isset($data['include_leads_custom_fields'])) {
                $include_leads_custom_fields = $data['include_leads_custom_fields'];
                unset($data['include_leads_custom_fields']);
            }

            if ($data['country'] == '' && $default_country != '') {
                $data['country'] = $default_country;
            }

            $data['billing_street']  = $data['address'];
            $data['billing_city']    = $data['city'];
            $data['billing_state']   = $data['state'];
            $data['billing_zip']     = $data['zip'];
            $data['billing_country'] = $data['country'];

            $data['is_primary'] = 1;
            $id                 = $this->clients_model->add($data, true);
            if ($id) {
                $primary_contact_id = get_primary_contact_user_id($id);

                if (isset($notes)) {
                    foreach ($notes as $note) {
                        $this->db->insert(db_prefix() . 'notes', [
                            'rel_id'         => $id,
                            'rel_type'       => 'customer',
                            'dateadded'      => $note['dateadded'],
                            'addedfrom'      => $note['addedfrom'],
                            'description'    => $note['description'],
                            'date_contacted' => $note['date_contacted'],
                        ]);
                    }
                }
                if (isset($consents)) {
                    foreach ($consents as $consent) {
                        unset($consent['id']);
                        unset($consent['purpose_name']);
                        $consent['lead_id']    = 0;
                        $consent['contact_id'] = $primary_contact_id;
                        $this->gdpr_model->add_consent($consent);
                    }
                }
                if (!has_permission('customers', '', 'view') && get_option('auto_assign_customer_admin_after_lead_convert') == 1) {
                    $this->db->insert(db_prefix() . 'customer_admins', [
                        'date_assigned' => date('Y-m-d H:i:s'),
                        'customer_id'   => $id,
                        'staff_id'      => get_staff_user_id(),
                    ]);
                }
                $this->leads_model->log_lead_activity($data['leadid'], 'not_lead_activity_converted', false, serialize([
                    get_staff_full_name(),
                ]));
                $default_status = $this->leads_model->get_status('', [
                    'isdefault' => 1,
                ]);
                $this->db->where('id', $data['leadid']);
                $this->db->update(db_prefix() . 'leads', [
                    'date_converted' => date('Y-m-d H:i:s'),
                    'status'         => $default_status[0]['id'],
                    'junk'           => 0,
                    'lost'           => 0,
                ]);
                // Check if lead email is different then client email
                $contact = $this->clients_model->get_contact(get_primary_contact_user_id($id));
                if ($contact->email != $original_lead_email) {
                    if ($original_lead_email != '') {
                        $this->leads_model->log_lead_activity($data['leadid'], 'not_lead_activity_converted_email', false, serialize([
                            $original_lead_email,
                            $contact->email,
                        ]));
                    }
                }
                if (isset($include_leads_custom_fields)) {
                    foreach ($include_leads_custom_fields as $fieldid => $value) {
                        // checked don't merge
                        if ($value == 5) {
                            continue;
                        }
                        // get the value of this leads custom fiel
                        $this->db->where('relid', $data['leadid']);
                        $this->db->where('fieldto', 'leads');
                        $this->db->where('fieldid', $fieldid);
                        $lead_custom_field_value = $this->db->get(db_prefix() . 'customfieldsvalues')->row()->value;
                        // Is custom field for contact ot customer
                        if ($value == 1 || $value == 4) {
                            if ($value == 4) {
                                $field_to = 'contacts';
                            } else {
                                $field_to = 'customers';
                            }
                            $this->db->where('id', $fieldid);
                            $field = $this->db->get(db_prefix() . 'customfields')->row();
                            // check if this field exists for custom fields
                            $this->db->where('fieldto', $field_to);
                            $this->db->where('name', $field->name);
                            $exists               = $this->db->get(db_prefix() . 'customfields')->row();
                            $copy_custom_field_id = null;
                            if ($exists) {
                                $copy_custom_field_id = $exists->id;
                            } else {
                                // there is no name with the same custom field for leads at the custom side create the custom field now
                                $this->db->insert(db_prefix() . 'customfields', [
                                    'fieldto'        => $field_to,
                                    'name'           => $field->name,
                                    'required'       => $field->required,
                                    'type'           => $field->type,
                                    'options'        => $field->options,
                                    'display_inline' => $field->display_inline,
                                    'field_order'    => $field->field_order,
                                    'slug'           => slug_it($field_to . '_' . $field->name, [
                                        'separator' => '_',
                                    ]),
                                    'active'        => $field->active,
                                    'only_admin'    => $field->only_admin,
                                    'show_on_table' => $field->show_on_table,
                                    'bs_column'     => $field->bs_column,
                                ]);
                                $new_customer_field_id = $this->db->insert_id();
                                if ($new_customer_field_id) {
                                    $copy_custom_field_id = $new_customer_field_id;
                                }
                            }
                            if ($copy_custom_field_id != null) {
                                $insert_to_custom_field_id = $id;
                                if ($value == 4) {
                                    $insert_to_custom_field_id = get_primary_contact_user_id($id);
                                }
                                $this->db->insert(db_prefix() . 'customfieldsvalues', [
                                    'relid'   => $insert_to_custom_field_id,
                                    'fieldid' => $copy_custom_field_id,
                                    'fieldto' => $field_to,
                                    'value'   => $lead_custom_field_value,
                                ]);
                            }
                        } elseif ($value == 2) {
                            if (isset($merge_db_fields)) {
                                $db_field = $merge_db_fields[$fieldid];
                                // in case user don't select anything from the db fields
                                if ($db_field == '') {
                                    continue;
                                }
                                if ($db_field == 'country' || $db_field == 'shipping_country' || $db_field == 'billing_country') {
                                    $this->db->where('iso2', $lead_custom_field_value);
                                    $this->db->or_where('short_name', $lead_custom_field_value);
                                    $this->db->or_like('long_name', $lead_custom_field_value);
                                    $country = $this->db->get(db_prefix() . 'countries')->row();
                                    if ($country) {
                                        $lead_custom_field_value = $country->country_id;
                                    } else {
                                        $lead_custom_field_value = 0;
                                    }
                                }
                                $this->db->where('userid', $id);
                                $this->db->update(db_prefix() . 'clients', [
                                    $db_field => $lead_custom_field_value,
                                ]);
                            }
                        } elseif ($value == 3) {
                            if (isset($merge_db_contact_fields)) {
                                $db_field = $merge_db_contact_fields[$fieldid];
                                if ($db_field == '') {
                                    continue;
                                }
                                $this->db->where('id', $primary_contact_id);
                                $this->db->update(db_prefix() . 'contacts', [
                                    $db_field => $lead_custom_field_value,
                                ]);
                            }
                        }
                    }
                }
                // set the lead to status client in case is not status client
                $this->db->where('isdefault', 1);
                $status_client_id = $this->db->get(db_prefix() . 'leads_status')->row()->id;
                $this->db->where('id', $data['leadid']);
                $this->db->update(db_prefix() . 'leads', [
                    'status' => $status_client_id,
                    'converted_by_lead_manager' => 1,
                    'last_status_change' => date('Y-m-d H:i:s'),
                ]);

                set_alert('success', _l('lead_to_client_base_converted_success'));

                if (is_gdpr() && get_option('gdpr_after_lead_converted_delete') == '1') {
                    // When lead is deleted
                    // move all proposals to the actual customer record
                    $this->db->where('rel_id', $data['leadid']);
                    $this->db->where('rel_type', 'lead');
                    $this->db->update('proposals', [
                        'rel_id'   => $id,
                        'rel_type' => 'customer',
                    ]);

                    $this->leads_model->delete($data['leadid']);

                    $this->db->where('userid', $id);
                    $this->db->update(db_prefix() . 'clients', ['leadid' => null]);
                }

                log_activity('Created Lead Client Profile [LeadID: ' . $data['leadid'] . ', ClientID: ' . $id . ']');
                hooks()->do_action('lead_converted_to_customer', ['lead_id' => $data['leadid'], 'customer_id' => $id]);
                redirect(admin_url('clients/client/' . $id));
            }
        }
    }
    public function chats()
    {
        $where = '';
        if (!is_admin()) {
            if (has_permission('lead_manager', '', 'view_own')) {
                $where = 'assigned =' . get_staff_user_id();
            }
        }
        $orderBy['cond'] = 'chats.sms_date';
        $orderBy['order'] = 'DESC';
        $data['leads'] = $this->lead_manager_model->get('', $where, $orderBy);
        $data['clients'] = $this->clients_model->get('', ['addedfrom' => get_staff_user_id()]);
        $data['staff'] = get_staff();
        $data['title'] = _l('lead_manager_lead_chats');
        $this->app_css->add('lm-chat-css', 'modules/lead_manager/assets/css/conversation.css');
        $this->load->view('admin/leads/conversations', $data);
    }
    public function load_conversation()
    {
        if ($this->input->post('lead_id') && $this->input->is_ajax_request()) {
            $data = [];
            $data['is_client'] = 0;
            if ($this->input->post('is_client') == 'lead') {
                $this->load->model('leads_model');
                $data['lead'] = $this->leads_model->get($this->input->post('lead_id'));
            } else {
                $data['is_client'] = 1;
                $data['lead'] = $this->clients_model->get($this->input->post('lead_id'));
            }

            $data['chats'] = $this->lead_manager_model->get_conversation($this->input->post('lead_id'), $data['is_client']);
            $data['staff'] = get_staff();
            $this->load->view('admin/leads/single_conversation', $data);
        }
    }
    public function auto_meeting_status_update()
    {
        $CI = &get_instance();
        if (!class_exists('lead_manager_model')) {
            $CI->load->model('lead_manager_model');
        }
        $params['apiSecret'] = get_option('zoom_secret_key');
        $params['apiKey'] = get_option('zoom_api_key');
        $params['path'] = 'meetings';
        $meetings = $this->lead_manager_model->zoomMeetingDetails('', ['status' => 'waiting']);
        $zoom = new ZoomJwtAPIWrapper($params);
        if (isset($meetings) && !empty($meetings)) {
            foreach ($meetings as $meeting) {
                $pathParams = array('meetingId' => $meeting['meeting_id']);
                $response = $zoom->doRequest('GET', '/meetings/{meetingId}', [], $pathParams, '');
                if (isset($response) && !empty($response) && isset($response['status'])) {
                    $update_data = ['id' => $meeting['id']];
                    $update_data['status'] = $response['status'];
                    $this->lead_manager_model->update_meeting_status($update_data);
                }
            }
        }
    }
    public function incoming_sms()
    {
        if ($this->input->is_ajax_request()) {
            $response = [];
            $post_data = $this->input->post();
            $result = $this->lead_manager_model->get_last_incoming_conversation($post_data['lm_leadid'], $post_data['last_sms_id'], $post_data['is_client']);
            $response['success'] = false;
            if (isset($result) && !empty($result)) {
                $response['message'] = $result->sms_body;
                $response['sms_id'] = $result->id;
                $response['success'] = true;
                $response['profile_image'] = base_url('assets/images/user-placeholder.jpg');
                $response['time'] = _dt($result->added_at);
                $response['sms_status'] = $result->sms_status;
                if ($post_data['is_client'] == 'client') {
                    $primary_contact_id = get_primary_contact_user_id($post_data['lm_leadid']);
                    if (isset($primary_contact_id) && !empty($primary_contact_id)) {
                        $response['profile_image'] = contact_profile_image_url($primary_contact_id);
                    }
                }
            }
            echo json_encode($response);
        }
    }
    public function serch_contacts_by_name()
    {
        if ($this->input->is_ajax_request()) {
            $htm = '';
            if ($this->input->post('type') == 'lead') {
                $htm = '';
                $where = '';
                if (!is_admin()) {
                    if (has_permission('lead_manager', '', 'view_own')) {
                        $where .= '(assigned =' . get_staff_user_id() . ')';
                        if (!empty($this->input->post('name'))) {
                            $where .= '(' . db_prefix() . 'leads.name LIKE "' . $this->input->post('name') . '%" OR ' . db_prefix() . 'leads.phonenumber LIKE "' . $this->input->post('name') . '%")';
                        }
                    }
                    if (has_permission('lead_manager', '', 'view')) {
                        if (!empty($this->input->post('name'))) {
                            $where .= '(' . db_prefix() . 'leads.name LIKE "' . $this->input->post('name') . '%" OR ' . db_prefix() . 'leads.phonenumber LIKE "' . $this->input->post('name') . '%")';
                        }
                    }
                } else {
                    if (!empty($this->input->post('name'))) {
                        $where .= '(' . db_prefix() . 'leads.name LIKE "' . $this->input->post('name') . '%" OR ' . db_prefix() . 'leads.phonenumber LIKE "' . $this->input->post('name') . '%")';
                    }
                }
                $orderBy['cond'] = 'chats.sms_date';
                $orderBy['order'] = 'DESC';
                $leads = $this->lead_manager_model->get('', $where, $orderBy);
                if (isset($leads) && !empty($leads)) {
                    foreach ($leads as $lead) {
                        $phoneNumber = isset($lead['phonenumber']) && !empty($lead['phonenumber']) ? $lead['phonenumber'] : _l('NA');
                        if ($this->input->post('is_whatsapp') && $this->input->post('is_whatsapp') == 'true') {
                            if (is_whats_app_enable($lead['id'], 'leads')) {
                                $last_conversation = get_last_message_conversation_whatsapp($lead['id'], ['is_client' => 'no']);
                                $count_unread_sms = get_total_unread_whatsapp_sms($lead['id'], ['is_client' => 'no', 'to_id' => get_staff_user_id()]);
                                $htm .= '<li class="contact wh_contact" data-contactid="' . $lead['id'] . '" id="' . $lead['id'] . '_contact">';
                                $htm .= '<div class="wrap">';
                                $htm .= '<img src="' . base_url('assets/images/user-placeholder.jpg') . '" alt="" />';
                                if (isset($last_conversation) && !empty($last_conversation)) {
                                    if (!empty($last_conversation->sms_date)) {
                                        $htm .= '<p class="pull-right last_active">' . time_ago($last_conversation->sms_date) . '</p>';
                                    } else {
                                        $htm .= '<p class="pull-right last_active">' . time_ago($last_conversation->added_at) . '</p>';
                                    }
                                }
                                $htm .= '<div class="meta">';
                                $htm .= '<p class="name">' . $lead['name'] . '</p>';
                                $htm .= '<small>' . $phoneNumber . '</small>';
                                if (isset($last_conversation) && !empty($last_conversation)) {
                                    $htm .= '<p class="preview">';
                                    if (isset($last_conversation->sms_direction) && $last_conversation->sms_direction == 'outgoing') {
                                        $htm .= _l('lm_wa_by_you_title');
                                    } else {
                                        $htm .= _l('lm_wa_by_lead_title');
                                    }
                                    if (isset($last_conversation->sms_body) && $last_conversation->is_files) {
                                        $htm .= _l('lm_wa_file_title');
                                    } else {
                                        $htm .= $last_conversation->sms_body;
                                    }
                                    $htm .= '</p>';
                                }
                                if ($count_unread_sms > 0) {
                                    $htm .= '<div class="count_unread_div"><small class="count_unread">' . $count_unread_sms . '</small>"</div>';
                                } else {
                                    $htm .= '<div class="count_unread_div"></div>';
                                }
                                $htm .= '</div>';
                                $htm .= '</div>';
                                $htm .= '</li>';
                            }
                        } else {
                            $last_conversation = get_last_message_conversation($lead['id'], ['is_client' => 'no']);
                            $htm .= '<li class="contact" onclick="loadContent(' . $lead['id'] . ')" id="' . $lead['id'] . '_contact">';
                            $htm .= '<div class="wrap">';
                            $htm .= '<img src="' . base_url('assets/images/user-placeholder.jpg') . '" alt="" />';
                            if (isset($last_conversation) && !empty($last_conversation)) {
                                if (!empty($last_conversation->sms_date)) {
                                    $htm .= '<p class="pull-right last_active">' . time_ago($last_conversation->sms_date) . '</p>';
                                } else {
                                    $htm .= '<p class="pull-right last_active">' . time_ago($last_conversation->added_at) . '</p>';
                                }
                            }
                            $htm .= '<div class="meta">';
                            $htm .= '<p class="name">' . $lead['name'] . '</p>';
                            $htm .= '<small>' . $phoneNumber . '</small>';
                            if (isset($last_conversation) && !empty($last_conversation)) {
                                $htm .= '<p class="preview">';
                                if (isset($last_conversation->sms_direction) && $last_conversation->sms_direction == 'outgoing') {
                                    $htm .= _l('lm_wa_by_you_title');
                                } else {
                                    $htm .= _l('lm_wa_by_lead_title');
                                }
                                $htm .= $last_conversation->sms_body;
                                $htm .= '</p>';
                            }
                            $htm .= '<div class="count_unread_div"></div';
                            $htm .= '</div>';
                            $htm .= '</div>';
                            $htm .= '</li>';
                        }
                    }
                }
            } else {
                $htm = '';
                $where = '';
                $clients = array();
                // if ($this->input->post('is_whatsapp') && $this->input->post('is_whatsapp') == 'true') {
                //     $where .= db_prefix() . 'clients.whatsapp_enable=1';
                // }
                if (!is_admin()) {
                    if (has_permission('lead_manager', '', 'view_own')) {
                        $where .= 'addedfrom=' . get_staff_user_id() . ' AND';
                        if (!empty($this->input->post('name'))) {
                            $where .= '(' . db_prefix() . 'clients.company LIKE "' . $this->input->post('name') . '%" OR ' . db_prefix() . 'contacts.phonenumber LIKE "' . $this->input->post('name') . '%")';
                        }
                    }
                    if (has_permission('lead_manager', '', 'view')) {
                        if (!empty($this->input->post('name'))) {
                            $where .= '(' . db_prefix() . 'clients.company LIKE "' . $this->input->post('name') . '%" OR ' . db_prefix() . 'contacts.phonenumber LIKE "' . $this->input->post('name') . '%")';
                        }
                    }
                } else {
                    if (!empty($this->input->post('name'))) {
                        $where .= '(' . db_prefix() . 'clients.company LIKE "' . $this->input->post('name') . '%" OR ' . db_prefix() . 'contacts.phonenumber LIKE "' . $this->input->post('name') . '%")';
                    }
                }
                $clients = $this->clients_model->get('', $where);
                if (isset($clients) && !empty($clients)) {
                    foreach ($clients as $client) {
                        $primary_contact_id = get_primary_contact_user_id($client['userid']);
                        if (isset($primary_contact_id) && !empty($primary_contact_id)) {
                            $profile_image = contact_profile_image_url($primary_contact_id);
                            $phoneNumber = isset($client['phonenumber']) && !empty($client['phonenumber']) ? $client['phonenumber'] : _l('NA');
                            if ($this->input->post('is_whatsapp') && $this->input->post('is_whatsapp') == 'true') {
                                if (is_whats_app_enable($primary_contact_id, 'customers')) {
                                    $last_conversation = get_last_message_conversation_whatsapp($client['userid'], ['is_client' => 'yes']);
                                    $htm .= '<li class="contact" onclick="loadContent(' . $client['userid'] . ')" id="' . $client['userid'] . '_contact">';
                                    $htm .= '<div class="wrap">';
                                    $htm .= '<img src="' . $profile_image . '" alt="" />';
                                    if (isset($last_conversation) && !empty($last_conversation)) {
                                        if (!empty($last_conversation->sms_date)) {
                                            $htm .= '<p class="pull-right last_active">' . time_ago($last_conversation->sms_date) . '</p>';
                                        } else {
                                            $htm .= '<p class="pull-right last_active">' . time_ago($last_conversation->added_at) . '</p>';
                                        }
                                    }
                                    $htm .= '<div class="meta">';
                                    $htm .= '<p class="name">' . $client['company'] . '</p>';
                                    $htm .= '<small>' . $phoneNumber . '</small>';
                                    if (isset($last_conversation) && !empty($last_conversation)) {
                                        $htm .= '<p class="preview">';
                                        if (isset($last_conversation->sms_direction) && $last_conversation->sms_direction == 'outgoing') {
                                            $htm .= _l('lm_wa_by_you_title');
                                        } else {
                                            $htm .= _l('lm_wa_by_lead_title');
                                        }
                                        if (isset($last_conversation->sms_body) && $last_conversation->is_files) {
                                            $htm .= _l('lm_wa_file_title');
                                        } else {
                                            $htm .= $last_conversation->sms_body;
                                        }
                                        $htm .= '</p>';
                                    }
                                    $htm .= '<div class="count_unread_div"></div';
                                    $htm .= '</div>';
                                    $htm .= '</div>';
                                    $htm .= '</li>';
                                }
                            } else {
                                $last_conversation = get_last_message_conversation($client['userid'], ['is_client' => 'yes']);
                                $htm .= '<li class="contact" onclick="loadContent(' . $client['userid'] . ')" id="' . $client['userid'] . '_contact">';
                                $htm .= '<div class="wrap">';
                                $htm .= '<img src="' . $profile_image . '" alt="" />';
                                if (isset($last_conversation->sms_date) && !empty($last_conversation->sms_date)) {
                                    $htm .= '<p class="pull-right last_active">' . time_ago($last_conversation->sms_date) . '</p>';
                                } else {
                                    $htm .= '<p class="pull-right last_active">' . isset($last_conversation->added_at) ?? time_ago($last_conversation->added_at) . '</p>';
                                }
                                $htm .= '<div class="meta">';
                                $htm .= '<p class="name">' . $client['company'] . '</p>';
                                $htm .= '<small>' . $phoneNumber . '</small>';
                                if (isset($last_conversation) && !empty($last_conversation)) {
                                    $htm .= '<p class="preview">';
                                    if (isset($last_conversation->sms_direction) && $last_conversation->sms_direction == 'outgoing') {
                                        $htm .= _l('lm_wa_by_you_title');
                                    } else {
                                        $htm .= _l('lm_wa_by_lead_title');
                                    }
                                    $htm .= $last_conversation->sms_body;
                                    $htm .= '</p>';
                                }
                                $htm .= '<div class="count_unread_div"></div';
                                $htm .= '</div>';
                                $htm .= '</div>';
                                $htm .= '</li>';
                            }
                        }
                    }
                }
            }
            echo $htm;
        }
    }
    public function incoming_sms_nofify()
    {
        if ($this->input->is_ajax_request()) {
            $response = [];
            $post_data = $this->input->post();
            $result = $this->lead_manager_model->get_incoming_notifications($post_data['ids'], $post_data['is_client']);
            $response['success'] = false;
            if (isset($result) && !empty($result)) {
                $response['data'] = $result;
                $response['success'] = true;
            }
            echo json_encode($response);
            die();
        }
    }
    public function get_mail_box_compose($id = '')
    {
        $data = array();
        if ($this->input->is_ajax_request()) {
            if (is_numeric($id)) {
                $data['lead'] = $this->lead_manager_model->get($id);
                $this->load->view('admin/mailbox/compose', $data);
            } else {
                $this->load->view('admin/mailbox/compose_new', $data);
            }
        }
    }

    public function mailbox()
    {
        $data = [];
        if ($this->input->get('dir') && $this->input->get('st')) {
            $data['direction'] = $this->input->get('dir');
            $data['status'] = $this->input->get('st');
        }
        $data['title'] = _l('lead_manager_lead_mailbox');
        $this->load->view('admin/mailbox/manage', $data);
    }
    public function mailbox_table()
    {
        if (!is_staff_member()) {
            ajax_access_denied();
        }
        $this->app->get_table_data(module_views_path('lead_manager', 'admin/mailbox/table'));
    }
    public function get_mail_box_configuration()
    {
        if ($this->input->is_ajax_request()) {
            $staffid = get_staff_user_id();
            if ($this->input->post()) {
                $response = array();
                $response = $this->lead_manager_model->update_mail_box_configuration($this->input->post());
                die(json_encode($response));
            }
            $data['setting'] = $this->lead_manager_model->get_mail_box_configuration($staffid);
            $this->load->view('admin/mailbox/configuration', $data);
        }
    }
    public function sendEmailMailbox()
    {
        if ($this->input->is_ajax_request()) {
            $staffid = get_staff_user_id();
            $todayDate = date("Y-m-d H:i:s");
            $mail_data = array();
            $staff_mailbox_detail = $this->lead_manager_model->get_mail_box_configuration($staffid);
            if ($this->input->post()) {
                $lead_id = get_lead_id_by_email($this->input->post('to'));
                $mail_data['staffid'] = $staffid;
                $mail_data['toid'] = $lead_id;
                $mail_data['is_client'] = 0;
                $mail_data['from_email'] = isset($staff_mailbox_detail) && !empty($staff_mailbox_detail) ? $staff_mailbox_detail->smtp_user : '';
                $mail_data['fromName'] = isset($staff_mailbox_detail) && !empty($staff_mailbox_detail) ? $staff_mailbox_detail->smtp_fromname : '';
                $mail_data['to_email'] = $this->input->post('to');
                $mail_data['subject'] = $this->input->post('subject');
                $mail_data['direction'] = 'outbound';
                $mail_data['message'] = $this->input->post('message');
                $mail_data['created_date'] = $todayDate;
                $mail_data['status'] = 'sending';
                $mail_data['is_attachment'] = isset($_FILES['attachments']) && $_FILES['attachments']['error'][0] != 4 ? 1 : 0;
                $mail_data['is_read'] = 1;
                $mail_data['mail_date'] = to_sql_date($todayDate, true);
                if ($this->input->post('to_cc')) {
                    $mail_data['to_cc'] = $this->input->post('to_cc');
                }
                if (isset($_FILES['attachments']) && !empty($_FILES['attachments']) && $_FILES['attachments']['error'][0] != 4) {
                    $mail_data['email_size'] = array_sum($_FILES['attachments']['size']);
                }
                $mailbox_id = $this->lead_manager_model->addSentMailBox($mail_data);
                if ($mailbox_id) {
                    if (isset($_FILES['attachments']) && !empty($_FILES['attachments']) && $_FILES['attachments']['error'][0] != 4) {
                        $uploaded_files = handle_lead_manager_mail_box_attachments_array($staffid, $mailbox_id);
                        $this->lead_manager_model->insertMailboxAttachments($uploaded_files, $mailbox_id, $staffid);
                        foreach ($uploaded_files as $index => $file) {
                            $uploaded_files[$index]['read'] = true;
                            $uploaded_files[$index]['attachment'] = LEAD_MANAGER_MAILBOX_FOLDER . $mailbox_id . '/' . $file['file_name'];
                        }
                        $this->lead_manager_model->add_attachment($uploaded_files);
                    }
                    //$response = $this->lead_manager_model->send_simple_email_lm($mail_data);
                     $response = $this->lead_manager_model->send_email($mailbox_id);
                    if (is_bool($response) && $response) {
                        $response = json_encode(['status' => 'success', 'message' => _l('lm_mb_mail_sent_success_alert')]);
                        die($response);
                    } else {
                        $response = json_encode(['status' => 'danger', 'message' => $response]);
                        die($response);
                    }
                }
            }
        }
    }
    public function view_mail_box_email($id)
    {
        if ($this->input->is_ajax_request()) {
            $data['email'] = $this->lead_manager_model->view_mail_box_email($id);
            $this->load->view('admin/mailbox/email_modal', $data);
        }
    }
    public function mailbox_mail_reply()
    {
        if ($this->input->is_ajax_request()) {
            $staffid = get_staff_user_id();
            $todayDate = date("Y-m-d H:i:s");
            $mail_data = array();
            $staff_mailbox_detail = $this->lead_manager_model->get_mail_box_configuration($staffid);
            if ($this->input->post()) {
                $lead_id = get_lead_id_by_email($this->input->post('to'));
                $mail_data['staffid'] = $staffid;
                $mail_data['toid'] = $lead_id;
                $mail_data['is_client'] = 0;
                $mail_data['fromName'] = isset($staff_mailbox_detail) && !empty($staff_mailbox_detail) ? $staff_mailbox_detail->smtp_fromname : '';
                $mail_data['from_email'] = isset($staff_mailbox_detail) && !empty($staff_mailbox_detail) ? $staff_mailbox_detail->smtp_user : '';
                $mail_data['to_email'] = $this->input->post('to');
                $mail_data['subject'] = $this->input->post('subject');
                $mail_data['direction'] = 'outbound';
                $mail_data['message'] = $this->input->post('message');
                $mail_data['created_date'] = to_sql_date($todayDate, true);
                $mail_data['status'] = 'sending';
                $mail_data['is_attachment'] = isset($_FILES['attachments']) && $_FILES['attachments']['error'][0] != 4 ? 1 : 0;
                $mail_data['is_read'] = 1;
                $mail_data['mail_date'] = to_sql_date($todayDate, true);
                if ($this->input->post('to_cc')) {
                    $mail_data['to_cc'] = $this->input->post('to_cc');
                }
                $mailbox_id = null;
                if ($this->input->post('is_draft')) {
                    $mailbox_id = $this->input->post('mail_id');
                    $this->lead_manager_model->update_mailbox_data(['status' => 'sending'], $mailbox_id);
                } else {
                    $mailbox_id = $this->lead_manager_model->addSentMailBox($mail_data);
                }
                if ($mailbox_id) {
                    if (isset($_FILES['attachments']) && !empty($_FILES['attachments']) && $_FILES['attachments']['error'][0] != 4) {
                        $uploaded_files = handle_lead_manager_mail_box_attachments_array($staffid, $mailbox_id);
                        $this->lead_manager_model->insertMailboxAttachments($uploaded_files, $mailbox_id, $staffid);
                        foreach ($uploaded_files as $index => $file) {

                            $uploaded_files[$index]['read'] = true;
                            $uploaded_files[$index]['attachment'] = LEAD_MANAGER_MAILBOX_FOLDER . $mailbox_id . '/' . $file['file_name'];
                        }
                        $this->lead_manager_model->add_attachment($uploaded_files);
                    }
                    // $response = $this->lead_manager_model->send_simple_email_lm($mail_data);
                    $response = $this->lead_manager_model->send_email($mailbox_id);
                    if (is_bool($response) && $response) {
                        $response = json_encode(['status' => 'success', 'message' => _l('lm_mb_mail_sent_success_alert')]);
                        die($response);
                    } else {
                        $response = json_encode(['status' => 'danger', 'message' => $response]);
                        die($response);
                    }
                }
            }
        }
    }
    public function mailbox_mark_as_bulk()
    {
        if ($this->input->is_ajax_request()) {
            $response = array('status' => false, 'responseText' => _l('lm_mb_bulk_update_danger_alert'));
            if ($this->input->post()) {
                $post_data = $this->input->post();
                if ($post_data['action'] == 'star') {
                    $post_data['is_favourite'] = 1;
                } elseif ($post_data['action'] == 'unstar') {
                    $post_data['is_favourite'] = 0;
                } elseif ($post_data['action'] == 'bookmark') {
                    $post_data['is_bookmark'] = 1;
                } elseif ($post_data['action'] == 'unbookmark') {
                    $post_data['is_bookmark'] = 0;
                } elseif ($post_data['action'] == 'delete') {
                    $resp = $this->lead_manager_model->mailbox_mark_as_bulk_delete($post_data);
                    $response = array('status' => 'success', 'responseText' => _l('lm_mb_bulk_update_success_alert_' . $post_data['action']));
                    die(json_encode($response));
                }
                $rows = $this->lead_manager_model->mailbox_mark_as_bulk($post_data);
                if ($rows) {
                    $response = array('status' => 'success', 'responseText' => _l('lm_mb_bulk_update_success_alert_' . $post_data['action']));
                    die(json_encode($response));
                } else {
                    $response = array('status' => 'danger', 'responseText' => _l('lm_mb_bulk_update_danger_alert'));
                    die(json_encode($response));
                }
            }
        }
    }
    public function DraftEmailMailbox()
    {
        if ($this->input->is_ajax_request()) {
            $staffid = get_staff_user_id();
            $todayDate = date("Y-m-d H:i:s");
            $mail_data = array();
            $staff_mailbox_detail = $this->lead_manager_model->get_mail_box_configuration($staffid);
            if ($this->input->post()) {
                $lead_id = get_lead_id_by_email($this->input->post('to'));
                $mail_data['staffid'] = $staffid;
                $mail_data['toid'] = $lead_id;
                $mail_data['is_client'] = 0;
                $mail_data['from_email'] = isset($staff_mailbox_detail) && !empty($staff_mailbox_detail) ? $staff_mailbox_detail->smtp_user : '';
                $mail_data['fromName'] = isset($staff_mailbox_detail) && !empty($staff_mailbox_detail) ? $staff_mailbox_detail->smtp_fromname : '';
                $mail_data['to_email'] = $this->input->post('to');
                $mail_data['subject'] = $this->input->post('subject');
                $mail_data['direction'] = 'outbound';
                $mail_data['message'] = $this->input->post('message');
                $mail_data['created_date'] = $todayDate;
                $mail_data['status'] = 'draft';
                $mail_data['is_attachment'] = isset($_FILES['attachments']) && $_FILES['attachments']['error'][0] != 4 ? 1 : 0;
                $mail_data['is_read'] = 1;
                $mail_data['mail_date'] = to_sql_date($todayDate, true);
                $mailbox_id = $this->lead_manager_model->addSentMailBox($mail_data);
                if (isset($_FILES['attachments']) && !empty($_FILES['attachments']) && $_FILES['attachments']['error'][0] != 4) {
                    $uploaded_files = handle_lead_manager_mail_box_attachments_array($staffid, $mailbox_id);
                    $this->lead_manager_model->insertMailboxAttachments($uploaded_files, $mailbox_id, $staffid);
                }
                echo $mailbox_id;
                die;
            }
        }
    }
    public function mailbox_mark_as_single()
    {
        if ($this->input->is_ajax_request()) {
            $response = array('status' => false, 'responseText' => _l('lm_mb_bulk_update_danger_alert'));
            if ($this->input->post()) {
                $post_data = $this->input->post();
                if ($post_data['action'] == 'star') {
                    $post_data['is_favourite'] = 1;
                } elseif ($post_data['action'] == 'unstar') {
                    $post_data['is_favourite'] = 0;
                } elseif ($post_data['action'] == 'bookmark') {
                    $post_data['is_bookmark'] = 1;
                } elseif ($post_data['action'] == 'unbookmark') {
                    $post_data['is_bookmark'] = 0;
                } elseif ($post_data['action'] == 'delete') {
                    $resp = $this->lead_manager_model->mailbox_mark_as_bulk_delete($post_data);
                    $response = array('status' => 'success', 'responseText' => _l('lm_mb_bulk_update_success_alert_' . $post_data['action']));
                    die(json_encode($response));
                }
                $rows = $this->lead_manager_model->mailbox_mark_as_bulk($post_data);
                if ($rows) {
                    $response = array('status' => 'success', 'responseText' => _l('lm_mb_bulk_update_success_alert_' . $post_data['action']));
                    die(json_encode($response));
                } else {
                    $response = array('status' => 'danger', 'responseText' => _l('lm_mb_bulk_update_danger_alert'));
                    die(json_encode($response));
                }
            }
        }
    }
    public function view_email($id)
    {
        $staffid = get_staff_user_id();
        $data['mail'] = $this->lead_manager_model->view_mail_box_email($id);
        if (isset($data['mail']) && !empty($data['mail'])) {
            $data['attachments'] = $this->lead_manager_model->get_mail_box_email_attachments($id);
        }
        if (isset($data['mail']) && !empty($data['mail']) && $data['mail']->is_read == 0) {
            $this->lead_manager_model->update_mailbox_data(['is_read' => 1], $id);
        }
        $data['next_mail_id'] = $this->lead_manager_model->view_mail_box_email_next($id, $staffid);
        $data['prev_mail_id'] = $this->lead_manager_model->view_mail_box_email_prev($id, $staffid);
        $data['title'] = _l('lead_manger_permission_email');
        $this->load->view('admin/mailbox/view_mail', $data);
    }
    public function download_attachemnts($id)
    {
        $this->load->library('zip');
        $path = LEAD_MANAGER_MAILBOX_FOLDER . $id . '/';
        $this->zip->read_dir($path, false);
        $this->zip->download($id . '.zip');
    }
    public function whatsapp()
    {
        $where = '';
        if (!is_admin()) {
            if (has_permission('lead_manager', '', 'view_own') || has_permission('lead_manager', '', 'view')) {
                $where .= 'assigned =' . get_staff_user_id();
            }
        }
        $data['leads'] = $this->lead_manager_model->get('', $where);
        $data['clients'] = $this->clients_model->get('', ['addedfrom' => get_staff_user_id()]);
        $data['staff'] = get_staff();
        $data['title'] = _l('lead_manager_whatsapp_ch');
        $this->app_css->add('lm-chat-css', 'modules/lead_manager/assets/css/conversation.css');
        $this->load->view('admin/whatsapp/chat', $data);
    }
    public function load_conversation_whatsapp()
    {
        if ($this->input->post('lead_id') && $this->input->is_ajax_request()) {
            $data = [];
            $data['is_client'] = 0;
            if ($this->input->post('is_client') == 'lead') {
                $this->load->model('leads_model');
                $data['lead'] = $this->leads_model->get($this->input->post('lead_id'));
            } else {
                $data['is_client'] = 1;
                $data['lead'] = $this->clients_model->get($this->input->post('lead_id'));
            }
            $data['chats'] = $this->lead_manager_model->get_conversation_whatsapp($this->input->post('lead_id'), $data['is_client']);
            $data['staff'] = get_staff();
            $this->load->view('admin/leads/single_conversation', $data);
        }
    }
    public function whatsapp_notifications()
    {
        $staff_id = get_staff_user_id();
        $lead_unread = $this->lead_manager_model->count_unread_whatsapp_messages_lead($staff_id);
        $client_unread = $this->lead_manager_model->count_unread_whatsapp_messages_client($staff_id);
        echo json_encode([
            'leads' => $lead_unread,
            'clients' => $client_unread
        ]);
    }
    public function update_incoming_whatsapp_sms()
    {
        if ($this->input->is_ajax_request()) {
            $response = [];
            $post_data = $this->input->post();
            $result = $this->lead_manager_model->update_last_incoming_whatsapp($post_data);
            $response['success'] = false;
            if (isset($result) && !empty($result)) {
                $response['success'] = true;
            }
            echo json_encode($response);
        }
    }
    public function send_whatsapp_sms()
    {
        $data = array();
        $lead = '';
        $msg_response = [];
        $response = ['success' => false];
        $staff_id = get_staff_user_id();
        if (get_option('call_twilio_active')) {
            $sid  = get_option('call_twilio_account_sid');
            $token  = get_option('call_twilio_auth_token');
            $twilio = new Client($sid, $token);
            $post_data = $this->input->post();
            if (isset($post_data['is_client']) && $post_data['is_client'] == 'client') {
                $lead = $this->clients_model->get_contact($post_data['lm_leadid']);
                if (!isset($lead) && empty($lead)) {
                    $lead = $this->clients_model->get($post_data['lm_leadid']);
                }
            }
            if (isset($post_data['is_client']) && $post_data['is_client'] == 'lead') {
                $lead = $this->lead_manager_model->get($post_data['lm_leadid']);
            }
            $phoneNumber = $lead->phonenumber;
            $staff_twilio_number = get_staff_own_twilio_number();
            if (!$staff_twilio_number) {
                $response['error'] = _l('lead_manager_twilio_number_not_assigned');
                echo json_encode($response);
                die;
            }
            if (!isset($post_data['message'])) {
                $template = $this->lead_manager_model->get_whatsapp_templates(array('status' => 'active'));
                if (isset($template) && !empty($template)) {
                    $post_data['message'] = $template->body_data;
                }
                if (!isset($post_data['message'])) {
                    $response['error'] = _l('lm_wh_template_not_found');
                    echo json_encode($response);
                    die;
                }
            }
            try {
                $message = $twilio->messages
                    ->create(
                        "whatsapp:" . $phoneNumber, // to
                        [
                            "from" => "whatsapp:" . $staff_twilio_number,
                            "body" => clear_textarea_breaks(nl2br($post_data['message']))
                        ]
                    );
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
            } catch (Exception $e) {
                $response['error'] = 'Twilio whatsapp ' . $e->getMessage();
            }
            if ($response['success']) {
                $data['type'] = 'whatsapp';
                $data['lead_id'] = $post_data['lm_leadid'];
                $data['date'] = date("Y-m-d H:i:s");
                $data['description'] = $post_data['message'];
                $data['additional_data'] = null;
                $data['staff_id'] = isset($post_data['is_client']) ? $staff_id : $lead->assigned;
                $data['direction'] = 'outgoing';
                $data['is_client'] = $post_data['is_client'] == 'client' ? 1 : 0;
                $this->lead_manager_model->lead_manger_activity_log($data);
                if ($post_data['is_client'] != 'client') {
                    $this->lead_manager_model->update_last_contact($post_data['lm_leadid']);
                    $response['profile_image'] = base_url('assets/images/user-placeholder.jpg');
                } else {
                    $primary_contact_id = get_primary_contact_user_id($post_data['lm_leadid']);
                    if (isset($primary_contact_id) && !empty($primary_contact_id)) {
                        $response['profile_image'] = contact_profile_image_url($primary_contact_id);
                    }
                }
                $response['sms_id'] = $this->lead_manager_model->create_conversation_whatsaap($msg_response, $data);
                $response['time'] = _dt(date("Y-m-d H:i:s"));
                $response['sms_status'] = $msg_response['status'];
                $response['message'] = $msg_response['body'];
            }
            echo json_encode($response);
            die;
        }
        $response = ['error' => 'Gateway not active'];
        echo json_encode($response); die;
    }
    public function whatsapp_total_unread()
    {
        $staff_id = get_staff_user_id();
        $total = $this->lead_manager_model->total_unread_whatsapp_messages($staff_id);
        echo json_encode([
            'total' => $total
        ]);
    }
    public function serch_contacts_by_filter()
    {
        if ($this->input->is_ajax_request()) {
            $htm = '';
            if ($this->input->post('type') == 'lead') {
                $htm = '';
                $where = '';
                if (!is_admin()) {
                    if (has_permission('lead_manager', '', 'view_own')) {
                        $where .= '(assigned =' . get_staff_user_id() . ')';
                        if (!empty($this->input->post('name'))) {
                            $where .= 'AND (' . db_prefix() . 'leads.name LIKE "' . $this->input->post('name') . '%" OR ' . db_prefix() . 'leads.phonenumber LIKE "' . $this->input->post('name') . '%")';
                        }
                    }
                    if (has_permission('lead_manager', '', 'view')) {
                        if (!empty($this->input->post('name'))) {
                            $where .= '(' . db_prefix() . 'leads.name LIKE "' . $this->input->post('name') . '%" OR ' . db_prefix() . 'leads.phonenumber LIKE "' . $this->input->post('name') . '%")';
                        }
                    }
                } else {
                    if (!empty($this->input->post('name'))) {
                        $where .= '(' . db_prefix() . 'leads.name LIKE "' . $this->input->post('name') . '%" OR ' . db_prefix() . 'leads.phonenumber LIKE "' . $this->input->post('name') . '%")';
                    }
                }
                // if ($this->input->post('is_whatsapp') && $this->input->post('is_whatsapp') == 'true') {
                //     $where .= ' AND ' . db_prefix() . 'leads.whatsapp_enable=1';
                // }
                $leads = $this->lead_manager_model->get('', $where);
                if (isset($leads) && !empty($leads)) {
                    foreach ($leads as $lead) {
                        $phoneNumber = isset($lead['phonenumber']) && !empty($lead['phonenumber']) ? $lead['phonenumber'] : _l('NA');
                        if ($this->input->post('is_whatsapp') && $this->input->post('is_whatsapp') == 'true') {
                            if (is_whats_app_enable($lead['id'], 'leads')) {
                                if (get_filtered_whatsapp_sms($lead['id'], ['is_client' => 'no', 'to_id' => get_staff_user_id(), 'is_read' => $this->input->post('filter_by')])) {
                                    $count_unread_sms = get_total_unread_whatsapp_sms($lead['id'], ['is_client' => 'no', 'to_id' => get_staff_user_id()]);
                                    $last_conversation = get_last_message_conversation_whatsapp($lead['id'], ['is_client' => 'no']);
                                    $sms_body = isset($last_conversation->sms_body) && !empty($last_conversation->sms_body) ? $last_conversation->sms_body : '';
                                    $htm .= '<li class="contact" onclick="loadContentWhatsapp(' . $lead['id'] . ')" id="' . $lead['id'] . '_contact">';
                                    $htm .= '<div class="wrap">';
                                    $htm .= '<img src="' . base_url('assets/images/user-placeholder.jpg') . '" alt="" />';
                                    $htm .= '<div class="meta">';
                                    $htm .= '<p class="name">' . $lead['name'] . '</p>';
                                    $htm .= '<small>' . $phoneNumber . '</small>';
                                    $htm .= '<p class="preview">' . $sms_body . '</p>';
                                    //check counting of sms/messages
                                    if ($count_unread_sms > 0) {
                                        $htm .= '<div class="count_unread_div"><small class="count_unread">' . $count_unread_sms . '</small>"</div>';
                                    } else {
                                        $htm .= '<div class="count_unread_div"></div>';
                                    }
                                    $htm .= '</div>';
                                    $htm .= '</div>';
                                    $htm .= '</li>';
                                }
                            }
                        } else {
                            if (get_answered_coversation_sms($lead['id'], ['is_client' => 'no', 'to_id' => get_staff_user_id(), 'is_answered' => $this->input->post('filter_by')])) {
                                $last_conversation = get_last_message_conversation($lead['id'], ['is_client' => 'no']);
                                $sms_body = isset($last_conversation->sms_body) && !empty($last_conversation->sms_body) ? $last_conversation->sms_body : '';
                                $htm .= '<li class="contact" onclick="loadContent(' . $lead['id'] . ')" id="' . $lead['id'] . '_contact">';
                                $htm .= '<div class="wrap">';
                                $htm .= '<img src="' . base_url('assets/images/user-placeholder.jpg') . '" alt="" />';
                                $htm .= '<div class="meta">';
                                $htm .= '<p class="name">' . $lead['name'] . '</p>';
                                $htm .= '<small>' . $phoneNumber . '</small>';
                                $htm .= '<p class="preview">' . $sms_body . '</p>';
                                $htm .= '<div class="count_unread_div"></div';
                                $htm .= '</div>';
                                $htm .= '</div>';
                                $htm .= '</li>';
                            }
                        }
                    }
                }
            } else {
                $htm = '';
                $where = '';
                $clients = array();
                if ($this->input->post('is_whatsapp') && $this->input->post('is_whatsapp') == 'true') {
                    $where .= db_prefix() . 'clients.whatsapp_enable=1';
                }
                if (!is_admin()) {
                    if (has_permission('lead_manager', '', 'view_own')) {
                        $where .= '(' . db_prefix() . 'clients.company LIKE "' . $this->input->post('name') . '%" OR ' . db_prefix() . 'contacts.phonenumber LIKE "' . $this->input->post('name') . '%")';
                    }
                    if (has_permission('lead_manager', '', 'view')) {
                        $where .= '(' . db_prefix() . 'clients.company LIKE "' . $this->input->post('name') . '%" OR ' . db_prefix() . 'contacts.phonenumber LIKE "' . $this->input->post('name') . '%")';
                    }
                } else {
                    $where .= '(' . db_prefix() . 'clients.company LIKE "' . $this->input->post('name') . '%" OR ' . db_prefix() . 'contacts.phonenumber LIKE "' . $this->input->post('name') . '%")';
                }
                $clients = $this->clients_model->get('', $where);
                if (isset($clients) && !empty($clients)) {
                    foreach ($clients as $client) {
                        $primary_contact_id = get_primary_contact_user_id($client['userid']);
                        if (isset($primary_contact_id) && !empty($primary_contact_id)) {
                            $profile_image = contact_profile_image_url($primary_contact_id);
                            $phoneNumber = isset($client['phonenumber']) && !empty($client['phonenumber']) ? $client['phonenumber'] : _l('NA');
                            if ($this->input->post('is_whatsapp') && $this->input->post('is_whatsapp') == 'true') {
                                if (is_whats_app_enable($client['userid'], 'customers')) {
                                    if (get_filtered_whatsapp_sms($client['userid'], ['is_client' => 'yes', 'to_id' => get_staff_user_id(), 'is_read' => $this->input->post('filter_by')])) {
                                        $last_conversation = get_last_message_conversation_whatsapp($client['userid'], ['is_client' => 'yes']);
                                        $sms_body = isset($last_conversation->sms_body) && !empty($last_conversation->sms_body) ? $last_conversation->sms_body : '';
                                        $htm .= '<li class="contact" onclick="loadContentWhatsapp(' . $client['userid'] . ')" id="' . $client['userid'] . '_contact">';
                                        $htm .= '<div class="wrap">';
                                        $htm .= '<img src="' . $profile_image . '" alt="" />';
                                        $htm .= '<div class="meta">';
                                        $htm .= '<p class="name">' . $client['company'] . '</p>';
                                        $htm .= '<small>' . $phoneNumber . '</small>';
                                        $htm .= '<p class="preview">' . $sms_body . '</p>';
                                        $htm .= '<div class="count_unread_div"></div';
                                        $htm .= '</div>';
                                        $htm .= '</div>';
                                        $htm .= '</li>';
                                    }
                                }
                            } else {
                                if (get_answered_coversation_sms($client['userid'], ['is_client' => 'yes', 'to_id' => get_staff_user_id(), 'is_answered' => $this->input->post('filter_by')])) {
                                    $last_conversation = get_last_message_conversation($client['userid'], ['is_client' => 'yes']);
                                    $sms_body = isset($last_conversation->sms_body) && !empty($last_conversation->sms_body) ? $last_conversation->sms_body : '';
                                    $htm .= '<li class="contact" onclick="loadContent(' . $client['userid'] . ')" id="' . $client['userid'] . '_contact">';
                                    $htm .= '<div class="wrap">';
                                    $htm .= '<img src="' . $profile_image . '" alt="" />';
                                    $htm .= '<div class="meta">';
                                    $htm .= '<p class="name">' . $client['company'] . '</p>';
                                    $htm .= '<small>' . $phoneNumber . '</small>';
                                    $htm .= '<p class="preview">' . $sms_body . '</p>';
                                    $htm .= '<div class="count_unread_div"></div';
                                    $htm .= '</div>';
                                    $htm .= '</div>';
                                    $htm .= '</li>';
                                }
                            }
                        }
                    }
                }
            }
            echo $htm;
        }
    }
    public function sms_total_unread()
    {
        $staff_id = get_staff_user_id();
        $total = $this->lead_manager_model->get_total_unread_sms_by_staff($staff_id);
        echo json_encode([
            'total' => $total
        ]);
    }
    public function whatsapp_upload_file($sender_id, $sender_type)
    {
        echo handle_whatsapp_file_uploads($sender_id, $sender_type); die;
    }
    public function serch_sms_by_filter()
    {
        if ($this->input->is_ajax_request()) {
            $htm = '';
            if ($this->input->post('type') == 'lead') {
                $htm = '';
                $where = '';
                if (!is_admin()) {
                    if (has_permission('lead_manager', '', 'view_own')) {
                        $where .= '(assigned =' . get_staff_user_id() . ')';
                        if (!empty($this->input->post('name'))) {
                            $where .= 'AND (' . db_prefix() . 'leads.name LIKE "' . $this->input->post('name') . '%" OR ' . db_prefix() . 'leads.phonenumber LIKE "' . $this->input->post('name') . '%")';
                        }
                    }
                    if (has_permission('lead_manager', '', 'view')) {
                        if (!empty($this->input->post('name'))) {
                            $where .= '(' . db_prefix() . 'leads.name LIKE "' . $this->input->post('name') . '%" OR ' . db_prefix() . 'leads.phonenumber LIKE "' . $this->input->post('name') . '%")';
                        }
                    }
                } else {
                    if (!empty($this->input->post('name'))) {
                        $where .= '(' . db_prefix() . 'leads.name LIKE "' . $this->input->post('name') . '%" OR ' . db_prefix() . 'leads.phonenumber LIKE "' . $this->input->post('name') . '%")';
                    }
                }
                $leads = $this->lead_manager_model->get('', $where);
                if (isset($leads) && !empty($leads)) {
                    foreach ($leads as $lead) {
                        $phoneNumber = isset($lead['phonenumber']) && !empty($lead['phonenumber']) ? $lead['phonenumber'] : _l('NA');
                        if ($this->input->post('is_whatsapp') && $this->input->post('is_whatsapp') == 'true') {
                            if (is_whats_app_enable($lead['id'], 'leads')) {
                                if (get_filtered_whatsapp_sms($lead['id'], ['is_client' => 'no', 'to_id' => get_staff_user_id(), 'is_read' => $this->input->post('filter_by')])) {
                                    $count_unread_sms = get_total_unread_whatsapp_sms($lead['id'], ['is_client' => 'no', 'to_id' => get_staff_user_id()]);
                                    $last_conversation = get_last_message_conversation_whatsapp($lead['id'], ['is_client' => 'no']);
                                    $sms_body = isset($last_conversation->sms_body) && !empty($last_conversation->sms_body) ? $last_conversation->sms_body : '';
                                    $htm .= '<li class="contact wh_contact" data-contactid="' . $lead['id'] . '" id="' . $lead['id'] . '_contact">';
                                    $htm .= '<div class="wrap">';
                                    $htm .= '<img src="' . base_url('assets/images/user-placeholder.jpg') . '" alt="" />';
                                    $htm .= '<div class="meta">';
                                    $htm .= '<p class="name">' . $lead['name'] . '</p>';
                                    $htm .= '<small>' . $phoneNumber . '</small>';
                                    $htm .= '<p class="preview">' . $sms_body . '</p>';
                                    if ($count_unread_sms > 0) {
                                        $htm .= '<div class="count_unread_div"><small class="count_unread">' . $count_unread_sms . '</small>"</div>';
                                    } else {
                                        $htm .= '<div class="count_unread_div"></div>';
                                    }
                                    $htm .= '</div>';
                                    $htm .= '</div>';
                                    $htm .= '</li>';
                                }
                            }
                        } else {
                            if (get_answered_coversation_sms($lead['id'], ['is_client' => 'no', 'to_id' => get_staff_user_id(), 'is_answered' => $this->input->post('filter_by')])) {
                                $last_conversation = get_last_message_conversation($lead['id'], ['is_client' => 'no']);
                                $sms_body = isset($last_conversation->sms_body) && !empty($last_conversation->sms_body) ? $last_conversation->sms_body : '';
                                $htm .= '<li class="contact" onclick="loadContent(' . $lead['id'] . ')" id="' . $lead['id'] . '_contact">';
                                $htm .= '<div class="wrap">';
                                $htm .= '<img src="' . base_url('assets/images/user-placeholder.jpg') . '" alt="" />';
                                if (isset($last_conversation->sms_date) && !empty($last_conversation->sms_date)) {
                                    $htm .= '<p class="pull-right last_active">' . time_ago($last_conversation->sms_date) . '</p>';
                                } else {
                                    $htm .= '<p class="pull-right last_active">' . isset($last_conversation->added_at) ?? time_ago($last_conversation->added_at) . '</p>';
                                }
                                $htm .= '<div class="meta">';
                                $htm .= '<p class="name">' . $lead['name'] . '</p>';
                                $htm .= '<small>' . $phoneNumber . '</small>';
                                if (isset($last_conversation) && !empty($last_conversation)) {
                                    $htm .= '<p class="preview">';
                                    if (isset($last_conversation->sms_direction) && $last_conversation->sms_direction == 'outgoing') {
                                        $htm .= _l('lm_wa_by_you_title');
                                    } else {
                                        $htm .= _l('lm_wa_by_lead_title');
                                    }
                                    $htm .= $last_conversation->sms_body;
                                    $htm .= '</p>';
                                }
                                $htm .= '<div class="count_unread_div"></div';
                                $htm .= '</div>';
                                $htm .= '</div>';
                                $htm .= '</li>';
                            }
                        }
                    }
                }
            } else {
                $htm = '';
                $where = '';
                $clients = array();
                if ($this->input->post('is_whatsapp') && $this->input->post('is_whatsapp') == 'true') {
                    $where .= ' AND' . db_prefix() . 'clients.whatsapp_enable=1';
                }
                if (!is_admin()) {
                    if (has_permission('lead_manager', '', 'view_own')) {
                        $clients = $this->clients_model->get('', db_prefix() . 'clients.company LIKE "' . $this->input->post('name') . '%" AND addedfrom=' . get_staff_user_id() . $where);
                    }
                    if (has_permission('lead_manager', '', 'view')) {
                        $clients = $this->clients_model->get('', db_prefix() . 'clients.company LIKE "' . $this->input->post('name') . '%"' . $where);
                    }
                } else {
                    $clients = $this->clients_model->get('', db_prefix() . 'clients.company LIKE "' . $this->input->post('name') . '%"' . $where);
                }
                if (isset($clients) && !empty($clients)) {
                    foreach ($clients as $client) {
                        $primary_contact_id = get_primary_contact_user_id($client['userid']);
                        if (isset($primary_contact_id) && !empty($primary_contact_id)) {
                            $profile_image = contact_profile_image_url($primary_contact_id);
                            $phoneNumber = isset($client['phonenumber']) && !empty($client['phonenumber']) ? $client['phonenumber'] : _l('NA');
                            if ($this->input->post('is_whatsapp') && $this->input->post('is_whatsapp') == 'true') {
                                if (is_whats_app_enable($client['userid'], 'customers')) {
                                    if (get_filtered_whatsapp_sms($client['userid'], ['is_client' => 'yes', 'to_id' => get_staff_user_id(), 'is_read' => $this->input->post('filter_by')])) {
                                        $last_conversation = get_last_message_conversation_whatsapp($client['userid'], ['is_client' => 'yes']);
                                        $sms_body = isset($last_conversation->sms_body) && !empty($last_conversation->sms_body) ? $last_conversation->sms_body : '';
                                        $htm .= '<li class="contact wh_contact" data-contactid="' . $client['userid'] . '" id="' . $client['userid'] . '_contact">';
                                        $htm .= '<div class="wrap">';
                                        $htm .= '<img src="' . $profile_image . '" alt="" />';
                                        $htm .= '<div class="meta">';
                                        $htm .= '<p class="name">' . $client['company'] . '</p>';
                                        $htm .= '<small>' . $phoneNumber . '</small>';
                                        $htm .= '<p class="preview">' . $sms_body . '</p>';
                                        $htm .= '<div class="count_unread_div"></div';
                                        $htm .= '</div>';
                                        $htm .= '</div>';
                                        $htm .= '</li>';
                                    }
                                }
                            } else {
                                if (get_answered_coversation_sms($client['userid'], ['is_client' => 'yes', 'to_id' => get_staff_user_id(), 'is_answered' => $this->input->post('filter_by')])) {
                                    $last_conversation = get_last_message_conversation($client['userid'], ['is_client' => 'yes']);
                                    $sms_body = isset($last_conversation->sms_body) && !empty($last_conversation->sms_body) ? $last_conversation->sms_body : '';
                                    $htm .= '<li class="contact" onclick="loadContent(' . $client['userid'] . ')" id="' . $client['userid'] . '_contact">';
                                    $htm .= '<div class="wrap">';
                                    $htm .= '<img src="' . $profile_image . '" alt="" />';
                                    $htm .= '<div class="meta">';
                                    $htm .= '<p class="name">' . $client['company'] . '</p>';
                                    $htm .= '<small>' . $phoneNumber . '</small>';
                                    $htm .= '<p class="preview">' . $sms_body . '</p>';
                                    $htm .= '<div class="count_unread_div"></div';
                                    $htm .= '</div>';
                                    $htm .= '</div>';
                                    $htm .= '</li>';
                                }
                            }
                        }
                    }
                }
            }
            echo $htm;
        }
    }
    public function update_incoming_sms()
    {
        if ($this->input->is_ajax_request()) {
            $response['success'] = false;
            $post_data = $this->input->post();
            $result = $this->lead_manager_model->update_last_incoming_sms($post_data);
            if (isset($result) && !empty($result)) {
                $response['success'] = true;
            }
            echo json_encode($response);
        }
    }
    public function email_total_unread()
    {
        $staff_id = get_staff_user_id();
        echo json_encode([
            'total' => total_rows(db_prefix() . 'lead_manager_mailbox', ['direction' => 'inbound', 'is_read' => 0, 'staffid' => $staff_id])
        ]);
    }
}
