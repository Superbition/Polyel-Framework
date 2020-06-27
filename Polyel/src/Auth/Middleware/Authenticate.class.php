<?php

namespace Polyel\Auth\Middleware;

use Polyel\Auth\AuthManager as Auth;

class Authenticate
{
    public $middlewareType = "before";

    protected $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function process($request, $protector)
    {

    }
}