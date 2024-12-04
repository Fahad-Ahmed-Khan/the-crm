<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Sms_twilio_lead_manager extends App_sms
{
    // Account SID from twilio.com/console
    private $sid;

    // Auth Token from twilio.com/console
    private $token;

    // Twilio Phone Number
    private $phone;

    // service id for sms
    private $messagingServiceSid;

    public function __construct()
    {
        parent::__construct();

        $this->sid   = $this->get_option('twilio', 'account_sid');
        $this->token = $this->get_option('twilio', 'auth_token');
        $this->phone = get_staff_own_twilio_number();
        $this->messagingServiceSid = 'MG429428b75d9b78bd20c8953a00932a77';
        $this->add_gateway('twilio', [
            'name'    => 'Twilio',
            'info'    => '<p>Twilio SMS integration is one way messaging, means that your customers won\'t be able to reply to the SMS. Phone numbers must be in format <a href="https://www.twilio.com/docs/glossary/what-e164" target="_blank">E.164</a>. Click <a href="https://support.twilio.com/hc/en-us/articles/223183008-Formatting-International-Phone-Numbers" target="_blank">here</a> to read more how phone numbers should be formatted.</p><hr class="hr-10" />',
            'options' => [
                [
                    'name'  => 'account_sid',
                    'label' => 'Account SID',
                ],
                [
                    'name'  => 'auth_token',
                    'label' => 'Auth Token',
                ],
                [
                    'name'  => 'phone_number',
                    'label' => 'Twilio Phone Number',
                ],
            ],
        ]);
    }

    public function send($number, $message)
    {
        
        $response_data = [];
        try {
            $client = new Twilio\Rest\Client($this->sid, $this->token);
        } catch (Exception $e) {
            $this->set_error($e->getMessage(), false);

            return false;
        }

        try {
            $response = $client->messages->create(
                // The number to send the SMS
                $number,
                [
                 // A Twilio phone number you purchased at twilio.com/console
                    'from' => $this->phone,
                    'body' => $message,
                    'statusCallback' => base_url('admin/lead_manager/sms_control/incoming_sms_status_webhook')
                ]
            );
            $response_data['sid'] = $response->sid;
            $response_data['msg_service_id'] = $response->messagingServiceSid;
            $response_data['to'] = $response->to;
            $response_data['status'] = $response->status;
            $response_data['body'] = $response->body;
            $this->logSuccess($number, $message);
        } catch (Exception $e) {
            $this->set_error($e->getMessage());

            return false;
        }

        return json_encode($response_data);
    }
}
