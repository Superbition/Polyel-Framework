<?php


namespace Polyel\Http;

class RedirectBuilder
{
    public $url;

    public $status;

    public $headers;

    public function __construct($url, $status)
    {
        $this->url = $url;
        $this->status = $status;
    }
}