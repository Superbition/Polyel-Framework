<?php


namespace Polyel\Http;

class RedirectBuilder
{
    public $url;

    public $status;

    public $headers;

    public $errors = [];

    public $group = null;

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
}