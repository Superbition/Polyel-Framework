<?php

namespace Polyel\System;

use Closure;

abstract class ServiceSupplier
{
    private array $binds = [];

    private array $serverSingletons = [];

    private array $requestSingletons = [];

    public function __construct()
    {

    }

    abstract public function register();

    protected function bind(string $classToBind, Closure $classServiceSupplier)
    {
        $this->binds[] = ['class' => $classToBind, 'closure' => $classServiceSupplier];
    }

    protected function registerServerSingleton(string $serverSingletonClass, Closure $serverSingletonSupplier)
    {
        $this->serverSingletons[] = [
            'class' => $serverSingletonClass,
            'closure' => $serverSingletonSupplier
        ];

        return $this;
    }

    protected function registerRequestSingleton(string $requestSingletonClass, Closure $requestSingletonSupplier)
    {
        $this->requestSingletons[] = [
            'class' => $requestSingletonClass,
            'closure' => $requestSingletonSupplier,
            'defer' => false
        ];

        return $this;
    }

    public function defer()
    {
        if(!empty($this->requestSingletons))
        {
            $this->requestSingletons[array_key_last($this->requestSingletons)]['defer'] = true;
        }
    }

    public function getServicesToRegister()
    {
        $registeredServices = [
            'binds' => [],
            'serverSingletons' => [],
            'requestSingletons' => []
        ];

        if(!empty($this->binds))
        {
            $registeredServices['binds'] = $this->binds;
        }

        if(!empty($this->serverSingletons))
        {
            $registeredServices['serverSingletons'] = $this->serverSingletons;
        }

        if(!empty($this->requestSingletons))
        {
            $registeredServices['requestSingletons'] = $this->requestSingletons;
        }

        return $registeredServices;
    }
}