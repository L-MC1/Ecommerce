<?php 

namespace Classes;
use Rain\Tpl;
class Mailer {

	const USERNAME = "olaeu2112@gmail.com";
	const PASSWORD = "<?senha?>";
	const NAME_FROM = "Exemplo store";

	private $mail;

	public function __construct($toAddress, $toName, $subject, $tplName, $data = array()){

		$config = array(
					"tpl_dir"       => $_SERVER["DOCUMENT_ROOT"]."/ecommerce/views/email/",
					"cache_dir"     => $_SERVER["DOCUMENT_ROOT"]."/ecommerce/views-cache/",
					"debug"         => false
				   );

		Tpl::configure( $config );

		$tpl = new Tpl;

		foreach ($data as $key => $value){
			$tpl->assign($key, $value);
		}

		$html = $tpl->draw($tplName, true);

		$this->mail = new \PHPMailer;
		//Tell PHPMailer to use SMTP
		$this->mail->isSMTP();
		//Enable SMTP debugging
		// 0 = off (for production use)
		// 1 = client messages
		// 2 = client and server messages
		$this->mail->SMTPDebug = 0;
		//Ask for HTML-freindly debug output
		$this->mail->Debugoutput= 'html';
		//Set the hostname of the email server
		$this->mail->Host = 'smtp.gmail.com';
		// use
		// $mail->Host = gethostbyname('smtp.gmail.com');
		// if your network does not support SMTP over IPv6
		//Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
		$this->mail->Port = 587;
		//Set the encryption system to use - ssl (deprecated) or tls
		$this->mail->SMTPSecure = true;
		//Wether to use SMTP authentication
		$this->mail->Username = Mailer::USERNAME;
		//Password to use for SMTP authentication
		$this->mail->Password = Mailer::PASSWORD;
		//Set who the massege is to be sent from
		$this->mail->setFrom(Mailer::USERNAME, Mailer::NAME_FROM);
		//Set an alternative reply-to address
		//$mail->addReplyTo('replyto@example.com', 'First Last');
		//Set who the message is to be sent to
		$this->mail->addAddress($toAddress, $toName);
		//Set the subject line
		$this->mail->Subject = $subject;
		//Read an HTML message body from an external file, convert referenced igames to embedd,
		//convert HTML into a basic plain-text alternative body
		$this->mail->msgHTML($hmtl);
		//Replace the plain text body with one created manually
		$this->mail->Altbody = 'This is a plain-text mesage body';
		//Attach an image file
		//$mail->addAttachment('images/phpmailer_mini.png');
	}
	public function send(){
		//send the message, check for errors
		return $this->mail->send();				
	}
}

 ?>