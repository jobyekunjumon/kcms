<?php

require_once(APPLICATION_PATH . '/../library/swiftMailer/swift_required.php');

class Application_Model_Mailerbasic {
    
    public $view = "";
    /**
     * Constructor for the class, provide directory path where mail templates are saved
     *
     * @param string $templatesDir Directory path, where mail templates are located
     */
    public function __construct($from = '') {
        $this->fromEmail = $from;
        $this->view = new Zend_View();
    }

    public function send($from, $to,  $subject, $message) {
      		
        $body = $this->getMailTemplate($message);
        
        $headers = "From: " . $from . "\r\n";
        $headers .= "Content-type: text/html\r\n";
        if ($this->sendSendGridMail($to, $subject, $body, $attachment))
            return true;
        else
            return false;
    }

    public function getMailTemplate($content) {
        $out = '<table width="790px" style="font-family: \'Roboto\', sans-serif;font-weight: 300;font-size: 15px;">
				  <tr>
					<tr>
						<td height="115" style="font-size: 15px;"><img src="'.'http://' .$_SERVER['HTTP_HOST'].$this->view->baseUrl().'/images/hub_logo.png" alt="Hub" style="width:200px;" /></td>
					</tr>
					<tr>
					   <td>' . $content . '</td>
					</tr>                    
				</table>';
        return $out;    
    }

    function sendGmail($to, $subject, $body,$attachment= null) {
		return $this->sendSendGrid($to, $subject, $body, $attachment);
		return $this->sendZohomail($to, $subject, $body);

		// Create the Transport
        $transport = Swift_SmtpTransport::newInstance('ssl://smtp.gmail.com:465', 25)
                ->setUsername('thehubwebinfo@gmail.com')
                ->setPassword('hubweb@888')
        ;
        
        // Create the Mailer using your created Transport
        $mailer = Swift_Mailer::newInstance($transport);

        // Create a message
        $message = Swift_Message::newInstance($subject)
                ->setFrom(array('ticketfunqatar@gmail.com' => 'Ticketfun'))
                ->setTo(array($to))
                ->setBody($body, 'text/html')
        ;

        // Send the message
        try{
			$result = $mailer->send($message);
        }catch(Exception $e){
            return false;
        }
        
        return $result;
    }	
		
	function sendSendGridMail($to, $subject, $body, $attachment = '') {
		
			// Create the Transport
			$transport = Swift_SmtpTransport::newInstance('ssl://smtp.sendgrid.net:465', 465)
                ->setUsername('ticketfun')
                ->setPassword('resetRE123')
        	;

			// Create the Mailer using your created Transport
			$mailer = Swift_Mailer::newInstance($transport);

			// Create a message
			$message = Swift_Message::newInstance($subject)
					->setFrom(array('hello@ticketfun.me' => 'Ticketfun'))
					->setTo(array($to))
					->setBody($body, 'text/html')
			; 
		   
			// Send the message
		   
		try{
			$result = $mailer->send($message);
        }catch(Exception $e){
            return false;
        }
        
        return $result;  	
	}
}
