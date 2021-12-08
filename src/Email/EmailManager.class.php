<?php

namespace Polyel\Email;

use Polyel;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailManager
{
    public function __construct()
    {

    }

    private function createNewMailer()
    {
        $mailer = new PHPMailer(true);

        $mailer->isSMTP();
        $mailer->SMTPAuth = true;

        return $mailer;
    }

    private function setMailerEncryption(string $from, $mailer)
    {
        $encryptionTypes = [
            'starttls' => PHPMailer::ENCRYPTION_STARTTLS,
            'smtps' => PHPMailer::ENCRYPTION_SMTPS,
        ];

        $mailer->SMTPSecure = $encryptionTypes[config("email.senders.$from.encryption")];

        return $mailer;
    }

    private function setSmtpHost(string $from, $mailer)
    {
        $mailer->Host = config("email.senders.$from.host");

        return $mailer;
    }

    private function setSmtpPort(string $from, $mailer)
    {
        $mailer->Port = (int)config("email.senders.$from.port");

        return $mailer;
    }

    private function setSmtpAuthCredentials(string $from, $mailer)
    {
        $mailer->Username = config("email.senders.$from.username");
        $mailer->Password = config("email.senders.$from.password");

        return $mailer;
    }

    public function send(string $to, string $from, array $ccs, array $bccs, Email $email)
    {
        if(empty($from))
        {
            $from = config('email.default');
        }

        $mailer = $this->createNewMailer();

        $mailer = $this->setMailerEncryption($from, $mailer);
        $mailer = $this->setSmtpHost($from, $mailer);
        $mailer = $this->setSmtpPort($from, $mailer);
        $mailer = $this->setSmtpAuthCredentials($from, $mailer);

        $mailer->setFrom($mailer->Username, $email->fromName);

        $mailer->addAddress($to);

        foreach($ccs as $cc)
        {
            $mailer->addCC($cc);
        }

        foreach($bccs as $bcc)
        {
            $mailer->addBCC($bcc);
        }

        $mailer->isHTML($email->usingHTML);

        $mailer->Subject = $email->subject;

        $mailer->Body = $email->message;

        Polyel::task($mailer);
    }
}