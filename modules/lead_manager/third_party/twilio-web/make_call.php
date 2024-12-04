<?php
// Include the bundled autoload from the Twilio PHP Helper Library
require 'src/Twilio/autoload.php';
use Twilio\Rest\Client;

// Your Account SID and Auth Token from twilio.com/console
$account_sid = 'AC02418a2ae0177ff7e49abbb968dc1222';
$auth_token = '378cc0f6917eee446221398153027a3f';
// In production, these should be environment variables. E.g.:
// $auth_token = $_ENV["TWILIO_ACCOUNT_SID"]

// A Twilio number you own with Voice capabilities
$twilio_number = "+19293343969";

// Where to make a voice call (your cell phone?)
//$to_number = "+923033720463";
$to_number = "+919140908151";

$client = new Client($account_sid, $auth_token);
$call = $client->account->calls->create(
    $to_number,
    $twilio_number,
    array(
        "url" => "http://demo.twilio.com/docs/voice.xml"
    )
);
echo $call->sid;
// echo "<pre>";
// print_r($call);
// echo "</pre>";
// die();