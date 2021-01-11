<?php

namespace Polyel\System;

use Polyel;

class ServiceManager
{
    private array $registeredBinds;

    private array $registeredRequestSingletons;

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

        $this->registeredBinds = $registeredServices['binds'];

        foreach($registeredServices['globalSingletons'] as $serverSingleton)
        {
            Polyel::registerSingletonService($serverSingleton['class'], $serverSingleton['closure']);
        }

        $this->registeredRequestSingletons = $registeredServices['localSingletons'];
    }

    public function getRegisteredBinds()
    {
        return $this->registeredBinds;
    }

    public function getRegisteredRequestSingletons()
    {
        return $this->registeredRequestSingletons;
    }
}