<?php

namespace Polyel\System;

use Polyel;

class ServiceManager
{
    private array $binds;

    private array $localSingletons;

    public function __construct()
    {

    }

    public function processServiceSuppliers()
    {
        $serviceSuppliers = config('main.servicesSuppliers');

        foreach($serviceSuppliers as $serviceSupplier)
        {
            $supplier = new $serviceSupplier();

            $supplier->register();

            $this->resolveServicesIntoContainer($supplier->getServicesToRegister());
        }
    }

    protected function resolveServicesIntoContainer(array $registeredServices)
    {
        foreach($registeredServices['binds'] as $bindService)
        {
            Polyel::registerBindService($bindService['class'], $bindService['closure']);
        }

        $this->binds = $registeredServices['binds'];

        foreach($registeredServices['globalSingletons'] as $serverSingleton)
        {
            Polyel::registerSingletonService($serverSingleton['class'], $serverSingleton['closure']);
        }

        $this->localSingletons = $registeredServices['localSingletons'];
    }

    public function getBinds()
    {
        return $this->binds;
    }

    public function getLocalSingletons()
    {
        return $this->localSingletons;
    }
}