<?php

namespace Polyel\Email;

abstract class Email
{
    protected string $fromName;

    protected string $subject;

    protected string $message;

    abstract public function setFromName();

    abstract public function setSubject();

    abstract public function setMessage();

    protected function name(string $fromName)
    {
        $this->fromName = $fromName;

        return $this;
    }

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