<?php

namespace Polyel\Http;

use Polyel\View\View;

class Response
{
    private $view;

    private $httpStatusCode;

    // Used to store a redirect
    private $redirection;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    public function send($response)
    {
        // If a redirection is set, redirect to the destination
        if(isset($this->redirection))
        {
            // Call a redirect and end the response
            $response->redirect($this->redirection, $this->httpStatusCode);
            return;
        }

        $response->status($this->httpStatusCode);
        $response->end($this->view->render(""));
    }

    public function setStatusCode(int $code)
    {
        $this->httpStatusCode = $code;
    }

    public function redirect($url, $statusCode = 302)
    {
        // Setup a redirection happen when send() is called
        $this->redirection = $url;
        $this->httpStatusCode = $statusCode;
    }
}