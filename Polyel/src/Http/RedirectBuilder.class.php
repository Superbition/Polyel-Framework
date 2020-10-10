<?php


namespace Polyel\Http;

class RedirectBuilder
{
    public $url;

    public $status;

    public $headers;

    public $errors = [];

    public $group = null;

    public $flashMessage = [];

    public function __construct($url, $status)
    {
        $this->url = $url;
        $this->status = $status;
    }

    public function withErrors(array $errors, string $group = '')
    {
        $this->errors = $errors;

        $this->group = $group;

        return $this;
    }

    public function withFlash($flashType, $flashMessage)
    {
        $this->flashMessage['type'] = $flashType;

        $this->flashMessage['message'] = $flashMessage;

        return $this;
    }
}