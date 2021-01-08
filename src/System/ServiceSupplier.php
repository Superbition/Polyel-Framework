<?php

namespace Polyel\System;

use Closure;

abstract class ServiceSupplier
{
    private array $registeredService;

    public function __construct()
    {

    }

    abstract public function register();

    protected function registerBind(string $classToBind, Closure $classServiceSupplier)
    {
        $this->registeredService = [
            'type' => 'bind',
            'class' => $classToBind,
            'supplier' => $classServiceSupplier
        ];
    }

    protected function registerRequestSingleton(string $requestSingletonClass, Closure $requestSingletonSupplier)
    {
        $this->registeredService = [
            'type' => 'requestSingleton',
            'class' => $requestSingletonClass,
            'supplier' => $requestSingletonSupplier
        ];
    }

    protected function registerServerSingleton(string $serverSingletonClass, Closure $serverSingletonSupplier)
    {
        $this->registeredService = [
            'type' => 'serverSingleton',
            'class' => $serverSingletonClass,
            'supplier' => $serverSingletonSupplier
        ];
    }
}