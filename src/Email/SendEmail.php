<?php

namespace Polyel\Email;

class SendEmail
{
    private EmailManager $emailManager;

    private string $to;

    private string $from = '';

    private array $ccs = [];

    private array $bccs = [];

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

    public function cc(string $cc)
    {
        $this->ccs[] = $cc;

        return $this;
    }

    public function bcc(string $bcc)
    {
        $this->bccs[] = $bcc;

        return $this;
    }

    public function send(Email $email)
    {
        defer(function() use ($email)
        {
            $email
                ->setFromName()
                ->setSubject()
                ->setMessage();

            $this->emailManager->send(
                $this->to,
                $this->from,
                $this->ccs,
                $this->bccs,
                $email
            );
        });
    }
}