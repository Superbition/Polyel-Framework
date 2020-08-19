<?php


namespace Polyel\Http;

class RedirectBuilder
{
    public $url;

    public $status;

    public $headers;

    public $errors = [];

    public function __construct($url, $status)
    {
        $this->url = $url;
        $this->status = $status;
    }

    public function withErrors(array $errors)
    {
        $this->errors = $errors;

        return $this;
    }
}