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

    public function processServiceSuppliers($consoleRequest = false)
    {
        $serviceSuppliers = config('main.servicesSuppliers');

        foreach($serviceSuppliers as $serviceSupplier)
        {
            $supplier = new $serviceSupplier();

            $supplier->register();

            $this->resolveServicesIntoContainer($supplier->getServicesToRegister(), $consoleRequest);
        }
    }

    protected function resolveServicesIntoContainer(array $registeredServices, bool $consoleRequest = false)
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

        if($consoleRequest)
        {
            /*
             * A console request only has one DI Container, the main
             * Polyel container, so there is no need to not register
             * local singletons inside the main Polyel container as
             * well. Because local singletons can be registered inside
             * the main container, they can be defined as deferred but
             * not sharable.
             */
            foreach($registeredServices['localSingletons'] as $localSingleton)
            {
                Polyel::registerSingletonService(
                    $localSingleton['class'],
                    $localSingleton['closure'],
                    $localSingleton['defer'],
                    false
                );
            }
        }

        /*
         * Save singletons registered as local so they can be retrieved later
         * and used in a local context. Even if a console request takes place
         * local singletons are still kept inside the service manager so that
         * they can be determined if needed.
         */
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