<?php  defined('BASEPATH') OR exit('No direct script access allowed');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once('./application/libraries/vendor/autoload.php');

class Ses_lib
{
	public $CI;
	public function __construct()
	{
		
        $this->CI =& get_instance();
		$this->CI->load->config('ion_auth');	
		$this->CI->load->config('email_ses_config');


	}

	/**
	 * Send The mail
	 *
	 * @param Array() //access,product_name,email,password subject, others
	 *
	 * @return array|bool
	 * @author Akash
	 */
	public function sendMail($email='',$first_name = '',$param=array())
	{
	
		// echo $email;exit;

		

		


		// Replace sender@example.com with your "From" address.
		// This address must be verified with Amazon SES.
		$sender = $this->CI->config->item('email', 'SES');
		$senderName = $this->CI->config->item('sender_name', 'SES');

		// Replace recipient@example.com with a "To" address. If your account
		// is still in the sandbox, this address must be verified.
		$recipient = $email;

		// Replace smtp_username with your Amazon SES SMTP user name.
		$usernameSmtp = $this->CI->config->item('smtp_user_name', 'SES');

		// Replace smtp_password with your Amazon SES SMTP password.
		$passwordSmtp = $this->CI->config->item('smtp_password', 'SES');

		// Specify a configuration set. If you do not want to use a configuration
		// set, comment or remove the next line.
		// $configurationSet = 'ConfigSet';

		// If you're using Amazon SES in a region other than US West (Oregon),
		// replace email-smtp.us-west-2.amazonaws.com with the Amazon SES SMTP
		// endpoint in the appropriate region.
		$host = 'email-smtp.us-west-2.amazonaws.com';
		$port = 587;

		// The subject line of the email
		$subject = $param['subject'];
		

		// The plain-text body of the email
		// $bodyText =  "Email Test\r\nThis email was sent through the
		//     Amazon SES SMTP interface using the PHPMailer class.";

		// The HTML-formatted body of the email
		$bodyHtml = $param['message'];;

		$mail = new PHPMailer(true);

		try {
		    // Specify the SMTP settings.
		    $mail->isSMTP();
		    $mail->setFrom($sender, $senderName);
		    $mail->Username   = $usernameSmtp;
		    $mail->Password   = $passwordSmtp;
		    $mail->Host       = $host;
		    $mail->Port       = $port;
		    $mail->SMTPAuth   = true;
		    $mail->SMTPSecure = 'tls';
		    // $mail->addCustomHeader('X-SES-CONFIGURATION-SET', $configurationSet);

		    // Specify the message recipients.
		    $mail->addAddress($recipient);
		    // You can also add CC, BCC, and additional To recipients here.

		    // Specify the content of the message.
		    $mail->isHTML(true);
		    $mail->Subject    = $subject;
		    $mail->Body       = $bodyHtml;
		    // $mail->AltBody    = $bodyText;
		    $mail->Send();
		    return "Email sent!";
		} catch (phpmailerException $e) {
		    return "An error occurred. {$e->errorMessage()}"; //Catch errors from PHPMailer.
		} catch (Exception $e) {
		    return "Email not sent. {$mail->ErrorInfo}"; //Catch errors from Amazon SES.
		}

	}
	

}

?>
