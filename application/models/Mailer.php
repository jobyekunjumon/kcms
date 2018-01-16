<?php

require_once(APPLICATION_PATH . '/../library/swiftMailer/swift_required.php');

class Application_Model_Mailer {
  private $_mailerEmail = 'konnectydigital@gmail.com';
  private $_mailerEmailPassword = 'konnecty123456';
  private $_mailerConnectToPort = '25';
  private $_mailerProtocol = 'ssl';
  private $_mailerServerAddress = 'ssl://smtp.gmail.com:465';
  private $_from = array('konnectydigital@gmail.com' => 'Konnecty CMS');

  public function __construct() {
    $this->view = new Zend_View();
  }

  public function sendWelcomeMail($user) {
    $subject = 'Welcome to konnecty CMS';
    $mailBody = 'Dear '.$user['name'].', <br> Welcome to konnecty CMS.';
    $this->send($user['email'],$subject,$mailBody);
  }

  public function sendEmailVerificationMail($user,$verificationUrl) {
    $subject = 'Please verify your email';
    $mailBody = 'Dear '.$user['name'].', <br> Please click on below link to activate your email address.';
    $mailBody .= '<a href="'.$verificationUrl.'">Verfiy now</a>';
    $this->send($user['email'],$subject,$mailBody);
  }

  public function send($to,$subject,$mailBody) {
    // check whether script connects to the smtp server properly
    $hasSmtpConnection = false;
    $socketFle = @fsockopen('smtp host', 25) ;
    if ($socketFle !== false) {
        $res = fread($socketFle, 1024) ;
        if (strlen($res) > 0 && strpos($res, '220') === 0) {
            $hasSmtpConnection = true;
        }
    }

    // if there is smtp connection , send smtp mail. else send php mail
    if($hasSmtpConnection) {
      if($this->sendSMTPMail) $this->logEmail($to,$subject,$mailBody,1,'smtp');
      else if(@$this->sendPHPMail($to,$subject,$mailBody)) $this->logEmail($to,$subject,$mailBody,1,'php');
      else $this->logEmail($to,$subject,$mailBody,0,'');
    } else {
      if($this->sendPHPMail($to,$subject,$mailBody)) $this->logEmail($to,$subject,$mailBody,1,'php');
      else $this->logEmail($to,$subject,$mailBody,0,'');
    }
  }

  public function sendSMTPMail() {
    // Create the Transport
    $transport = Swift_SmtpTransport::newInstance($this->_mailerServerAddress, $this->_mailerConnectToPort)
            ->setUsername($this->_mailerEmail)
            ->setPassword($this->$_mailerEmailPassword)
    ;

    // Create the Mailer using your created Transport
    $mailer = Swift_Mailer::newInstance($transport);

    // Create  message
    $message = Swift_Message::newInstance($subject)
            ->setFrom($this->_from)
            ->setTo(array($to))
            ->setBody($mailBody, 'text/html');
    try {
      $result = $mailer->send($message);
      return $result;
    } catch (\Exception $e) {
      return false;
    }

    return false;
  }

  public function sendPHPMail($to,$subject,$mailBody) {
    $headers = 'From: '.$this->_mailerEmail . "\r\n";
    $headers .= 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion();
    if(@mail($to,$subject,$mailBody,$headers)) return true;
    return false;
  }

  public function logEmail($to,$subject,$mailBody,$mailStatus,$mailer) {
    $modelMailerLog = new Application_Model_DbTable_Composer();
    $modelMailerLog->setTableName('email_log');
    $modelMailerLog->setIdColumn('id_email_log_entry');

    $newLogEntry = array( 'mail_subject' => addslashes($subject),
                      'mail_body' => addslashes($mailBody),
                      'mailer_from_email' => $this->_mailerEmail,
                      'mail_to' => $to,
                      'mailer' => $mailer,
                      'entry_date' => date('Y-m-d H:i:s'),
                      'email_status' => $mailStatus,
                      );
    if($modelMailerLog->insertData($newLogEntry)) {
      return true;
    }

    return false;
  }
}
