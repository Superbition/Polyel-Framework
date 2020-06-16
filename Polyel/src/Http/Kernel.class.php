<?php

namespace Polyel\Http;

use Polyel\Container\Container;

class Kernel
{
    // The kernel service container for this HTTP request
    public $container;

    // The request service for the duration of this HTTP request
    public $request;

    // The response service for the duration of this HTTP request
    public $response;

    public function __construct()
    {
        $this->container = new Container();
        $this->request = $this->container->resolveClass(Request::class);
        $this->response = $this->container->resolveClass(Response::class);
    }

    public function setContainer(Container $container)
    {
        $this->container = $container;
    }
}