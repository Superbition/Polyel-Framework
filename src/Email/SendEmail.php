<?php

namespace Polyel\Email;

class SendEmail
{
    private EmailManager $emailManager;

    private string $to;

    private string $from = '';

    public function __construct(EmailManager $emailManager)
    {
        $this->emailManager = $emailManager;
    }

    public function from(string $from)
    {
        $this->from = $from;

        return $this;
    }

    public function to(string $to)
    {
        $this->to = $to;

        return $this;
    }

    public function send(Email $email)
    {
        $email
            ->setFromName()
            ->setSubject()
            ->setMessage();

        defer(function() use ($email)
        {
            $this->emailManager->send(
                $this->to,
                $this->from,
                $email
            );
        });
    }
}