<?php

namespace Polyel\Middleware;

class Middleware
{
    private $middlewares = [];

    public function __construct()
    {

    }

    public function register($requestMethod, $uri, $middleware)
    {
        $this->middlewares[$requestMethod][$uri] = $middleware;
    }
}