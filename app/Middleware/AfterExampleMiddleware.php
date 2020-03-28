<?php

namespace App\Middleware;

use Polyel\Middleware\Middleware;

class AfterExampleMiddleware extends Middleware
{
    public $middlewareType = "after";

    public function process($response)
    {

    }
}