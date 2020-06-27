<?php

namespace Polyel\Auth\Middleware;

use Polyel\Auth\AuthManager as Auth;

abstract class Authenticate implements AuthenticationOutcomes
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