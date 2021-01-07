<?php

namespace Polyel\System;

use Closure;

abstract class ServiceSupplier
{
    private array $binds = [];

    private array $requestSingletons = [];

    private array $serverSingletons = [];

    public function __construct()
    {

    }

    abstract public function register();

    protected function registerBind(string $classToBind, Closure $classServiceSupplier)
    {
        $this->binds[] = ['class' => $classToBind, 'supplier' => $classServiceSupplier];
    }

    protected function registerRequestSingleton(string $requestSingletonClass, Closure $requestSingletonSupplier)
    {
        $this->requestSingletons[] = ['class' => $requestSingletonClass, 'supplier' => $requestSingletonSupplier];
    }

    protected function registerServerSingleton(string $serverSingletonClass, Closure $serverSingletonSupplier)
    {
        $this->serverSingletons[] = ['class' => $serverSingletonClass, 'supplier' => $serverSingletonSupplier];
    }
}