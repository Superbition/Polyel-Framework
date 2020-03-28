<?php

namespace App\Middleware;

use Polyel\Middleware\Middleware;

class BeforeExampleMiddleware extends Middleware
{
    public $middlewareType = "before";

    public function process($request)
    {

    }
}