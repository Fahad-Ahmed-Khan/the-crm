<?php defined('BASEPATH') OR exit('No direct script access allowed');

$CI = &get_instance();
$staff_id = get_staff_user_id();
if(is_numeric($staff_id)){
    if (!class_exists('lead_manager_model')) {
        $CI->load->model('lead_manager_model');
    }
    $is_active_module = $CI->lead_manager_model->is_lead_manager_active();
    $options = null;
    if($is_active_module){
        $options = $CI->lead_manager_model->get_mail_box_configuration($staff_id);
    }

$config['useragent']    = get_option('mail_engine'); // phpmailer or codeigniter

$config['protocol']     = get_option('email_protocol');

$config['mailpath']     = "/usr/bin/sendmail"; // or "/usr/sbin/sendmail"

$config['smtp_host']    = isset($options) && !empty($options) ? trim($options->smtp_server) : '';

$config['smtp_user']    = isset($options) && !empty($options) ? trim($options->smtp_user) : '';

$config['smtp_pass']    = isset($options) && !empty($options) ? $CI->encryption->decrypt($options->smtp_password) : '';

$config['smtp_port']    = isset($options) && !empty($options) ? trim($options->smtp_port) : '';

$config['smtp_timeout'] = 30;

$config['smtp_fromname'] = isset($options) && !empty($options) ? trim($options->smtp_fromname) : '';

$config['smtp_crypto'] = isset($options) && !empty($options) ? $options->smtp_encryption : '';

$config['smtp_debug']       = 0;                        // PHPMailer's SMTP debug info level: 0 = off, 1 = commands, 2 = commands and data, 3 = as 2 plus connection status, 4 = low level data output.

$config['debug_output']     = 'html';                       // PHPMailer's SMTP debug output: 'html', 'echo', 'error_log' or user defined function with parameter $str and $level. NULL or '' means 'echo' on CLI, 'html' otherwise.

$config['smtp_auto_tls']    = false;                     // Whether to enable TLS encryption automatically if a server supports it, even if `smtp_crypto` is not set to 'tls'.

$config['smtp_conn_options'] = array();                 // SMTP connection options, an array passed to the function stream_context_create() when connecting via SMTP.

$config['wordwrap']     = true;

$config['mailtype']     = 'html';

$charset = strtoupper(get_option('smtp_email_charset'));
$charset = trim($charset);
if ($charset == '' || strcasecmp($charset,'utf8') == 'utf8') {
    $charset = 'utf-8';
}

$config['charset']      = $charset;

$config['validate']         = false;

$config['priority']         = 3;                        // 1, 2, 3, 4, 5; on PHPMailer useragent NULL is a possible option, it means that X-priority header is not set at all, see https://github.com/PHPMailer/PHPMailer/issues/449

$config['newline']      = "\r\n";

$config['crlf']         = "\r\n";

$config['bcc_batch_mode']   = false;

$config['bcc_batch_size']   = 200;

$config['encoding']         = '8bit';                   // The body encoding. For CodeIgniter: '8bit' or '7bit'. For PHPMailer: '8bit', '7bit', 'binary', 'base64', or 'quoted-printable'.

// DKIM Signing
// See https://yomotherboard.com/how-to-setup-email-server-dkim-keys/
// See http://stackoverflow.com/questions/24463425/send-mail-in-phpmailer-using-dkim-keys
// See https://github.com/PHPMailer/PHPMailer/blob/v5.2.14/test/phpmailerTest.php#L1708
$config['dkim_domain']      = '';                       // DKIM signing domain name, for exmple 'example.com'.

$config['dkim_private']     = '';                       // DKIM private key, set as a file path.

$config['dkim_private_string'] = '';                    // DKIM private key, set directly from a string.

$config['dkim_selector']    = '';                       // DKIM selector.

$config['dkim_passphrase']  = '';                       // DKIM passphrase, used if your key is encrypted.

$config['dkim_identity']    = '';                       // DKIM Identity, usually the email address used as the source of the email.

}else{
    $config['useragent']    = get_option('mail_engine'); // phpmailer or codeigniter

$config['protocol']     = get_option('email_protocol');

$config['mailpath']     = "/usr/bin/sendmail"; // or "/usr/sbin/sendmail"

$config['smtp_host']    = '';

$config['smtp_user']    = '';

$config['smtp_pass']    = '';

$config['smtp_port']    = '';

$config['smtp_timeout'] = 30;

$config['smtp_crypto'] = '';

$config['smtp_debug']       = 0;                        // PHPMailer's SMTP debug info level: 0 = off, 1 = commands, 2 = commands and data, 3 = as 2 plus connection status, 4 = low level data output.

$config['debug_output']     = 'html';                       // PHPMailer's SMTP debug output: 'html', 'echo', 'error_log' or user defined function with parameter $str and $level. NULL or '' means 'echo' on CLI, 'html' otherwise.

$config['smtp_auto_tls']    = false;                     // Whether to enable TLS encryption automatically if a server supports it, even if `smtp_crypto` is not set to 'tls'.

$config['smtp_conn_options'] = array();                 // SMTP connection options, an array passed to the function stream_context_create() when connecting via SMTP.

$config['wordwrap']     = true;

$config['mailtype']     = 'html';

$charset = strtoupper(get_option('smtp_email_charset'));
$charset = trim($charset);
if ($charset == '' || strcasecmp($charset,'utf8') == 'utf8') {
    $charset = 'utf-8';
}

$config['charset']      = $charset;

$config['validate']         = false;

$config['priority']         = 3;                        // 1, 2, 3, 4, 5; on PHPMailer useragent NULL is a possible option, it means that X-priority header is not set at all, see https://github.com/PHPMailer/PHPMailer/issues/449

$config['newline']      = "\r\n";

$config['crlf']         = "\r\n";

$config['bcc_batch_mode']   = false;

$config['bcc_batch_size']   = 200;

$config['encoding']         = '8bit';                   // The body encoding. For CodeIgniter: '8bit' or '7bit'. For PHPMailer: '8bit', '7bit', 'binary', 'base64', or 'quoted-printable'.

// DKIM Signing
// See https://yomotherboard.com/how-to-setup-email-server-dkim-keys/
// See http://stackoverflow.com/questions/24463425/send-mail-in-phpmailer-using-dkim-keys
// See https://github.com/PHPMailer/PHPMailer/blob/v5.2.14/test/phpmailerTest.php#L1708
$config['dkim_domain']      = '';                       // DKIM signing domain name, for exmple 'example.com'.

$config['dkim_private']     = '';                       // DKIM private key, set as a file path.

$config['dkim_private_string'] = '';                    // DKIM private key, set directly from a string.

$config['dkim_selector']    = '';                       // DKIM selector.

$config['dkim_passphrase']  = '';                       // DKIM passphrase, used if your key is encrypted.

$config['dkim_identity']    = '';                       // DKIM Identity, usually the email address used as the 
}