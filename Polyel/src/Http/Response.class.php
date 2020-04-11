<?php

namespace Polyel\Http;

use Polyel\View\View;

class Response
{
    private $view;

    private $httpStatusCode;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    public function send($response)
    {
        $response->status($this->httpStatusCode);
        $response->end($this->view->render(""));
    }

    public function setStatusCode(int $code)
    {
        $this->httpStatusCode = $code;
    }
}