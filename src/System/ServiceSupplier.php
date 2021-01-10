<?php

namespace Polyel\System;

use Closure;

abstract class ServiceSupplier
{
    private array $binds = [];

    private array $requestSingletons = [];

    private array $serverSingletons = [];

    private string $lastAddedSingletonType;

    public function __construct()
    {

    }

    abstract public function register();

    public function defer()
    {
        if(!is_null($this->lastAddedSingletonType))
        {
            if($this->lastAddedSingletonType === 'server')
            {
                $this->serverSingletons[array_key_last($this->serverSingletons)]['defer'] = true;
            }
            else if($this->lastAddedSingletonType === 'request')
            {
                $this->requestSingletons[array_key_last($this->requestSingletons)]['defer'] = true;
            }
        }
    }

    protected function registerBind(string $classToBind, Closure $classServiceSupplier)
    {
        $this->binds[] = ['class' => $classToBind, 'closure' => $classServiceSupplier];
    }

    protected function registerRequestSingleton(string $requestSingletonClass, Closure $requestSingletonSupplier)
    {
        $this->requestSingletons[] = [
            'class' => $requestSingletonClass,
            'closure' => $requestSingletonSupplier,
            'defer' => false
        ];

        $this->lastAddedSingletonType = 'request';

        return $this;
    }

    protected function registerServerSingleton(string $serverSingletonClass, Closure $serverSingletonSupplier)
    {
        $this->serverSingletons[] = [
            'class' => $serverSingletonClass,
            'closure' => $serverSingletonSupplier,
            'defer' => false
        ];

        $this->lastAddedSingletonType = 'server';

        return $this;
    }
}