<?php

namespace Polyel\Email;

abstract class Email
{
    protected string $subject;

    protected string $message;

    abstract protected function setSubject();

    abstract protected function setMessage();

    protected function subject(string $subject)
    {
        $this->subject = $subject;

        return $this;
    }

    protected function text(string $message)
    {
        $this->message = $message;

        return $this;
    }
}