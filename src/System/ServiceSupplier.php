<?php

namespace Polyel\System;

use Closure;

abstract class ServiceSupplier
{
    private array $binds = [];

    private array $singletons = [];

    public function __construct()
    {

    }

    abstract public function register();

    protected function bind(string $classToBind, Closure $classServiceSupplier)
    {
        $this->binds[] = ['class' => $classToBind, 'closure' => $classServiceSupplier];
    }

    protected function singleton(string $serverSingletonClass, Closure $serverSingletonSupplier)
    {
        $this->singletons[] = [
            'class' => $serverSingletonClass,
            'closure' => $serverSingletonSupplier,
            'scope' => 'local',
            'defer' => false
        ];

        return $this;
    }

    public function global()
    {
        if(!empty($this->singletons))
        {
            $this->singletons[array_key_last($this->singletons)]['scope'] = 'global';
        }
    }

    public function defer()
    {
        if(!empty($this->singletons))
        {
            $this->singletons[array_key_last($this->singletons)]['defer'] = true;
        }
    }

    public function getServicesToRegister()
    {
        $registeredServices = [
            'binds' => [],
            'globalSingletons' => [],
            'localSingletons' => []
        ];

        if(!empty($this->binds))
        {
            $registeredServices['binds'] = $this->binds;
        }

        foreach($this->singletons as $singleton)
        {
            if($singleton['scope'] === 'global')
            {
                $registeredServices['globalSingletons'][] = $singleton;
            }
            else
            {
                $registeredServices['localSingletons'][] = $singleton;
            }
        }

        return $registeredServices;
    }
}