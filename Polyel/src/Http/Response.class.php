<?php

namespace Polyel\Http;

use Polyel\View\View;

class Response
{
    private $view;

    private $headers;

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

        // Set all response headers before returning a response to client
        $this->setAllHeadersFor($response);

        $response->status($this->httpStatusCode);
        $response->end($this->view->render(""));
    }

    public function setStatusCode(int $code)
    {
        $this->httpStatusCode = $code;
    }

    private function setAllHeadersFor($response)
    {
        if(is_array($this->headers) && count($this->headers))
        {
            // All headers that were set during the request being handled...
            foreach($this->headers as $header => $value)
            {
                // Set headers for this request only
                $response->header($header, $value);
            }

            // Reset headers so they don't show up on other/next requests
            $this->headers = [];
        }

        // Reset headers so they don't show up on other/next requests
        $this->headers = [];
    }

    private function queueHeader($headerName, $headerValue)
    {
        // Queue headers, they will be set later just before sending the response to the client
        $this->headers[$headerName] = $headerValue;
    }

    public function redirect($url, $statusCode = 302)
    {
        // Setup a redirection happen when send() is called
        $this->redirection = $url;
        $this->httpStatusCode = $statusCode;
    }
}