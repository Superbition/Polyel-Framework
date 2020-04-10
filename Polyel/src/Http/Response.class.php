<?php

namespace Polyel\Http;

use Polyel\View\View;

class Response
{
    private $view;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    public function send($response)
    {
        $response->end($this->view->render(""));
    }
}