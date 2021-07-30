<?php
use PHPMailer\PHPMailer\PHPMailer;

require_once'phpmailer/Exception.php';
require_once'phpmailer/PHPMailer.php';
require_once'phpmailer/SMPT.php';



$mail = new($_POST['submit'])
{
    $name = $_POST['name'];
    $name = $_POST['ename'];
    $name = $_POST['message'];
}
try
{
    $mail->isSMPT();
    $mail->Host = 'smpt.gmail.com'
    $mail->SMPTAuth = true;
    $mail->Username = 'sashk.only@gmail.com';
    $mail->Password = 'p0s0s1mne4len';
    $mail->SMPTSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = '587';
    
    $mail->setFrom('sashk.only@gmailcom');
    $mail->addAddress('sashk.only@gmailcom');

    $mail->isHTML(true);
    $mail->Subject = 'Message Received (Contact Page)';
    $mail->Body = '<h3>Name : $name <br>Email: $email <br>Message : $message </h3>'

    $mail->send();
    $alert = '<div class="alert-sucess">
                <span>Message Sent! Thank you for contacting us!</span>
    
    </div>';
}




?>