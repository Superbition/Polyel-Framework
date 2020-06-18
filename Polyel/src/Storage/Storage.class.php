<?php

namespace Polyel\Storage;

class Storage
{
    // Holds the storage driver for local operators
    private $localStorage;

    public function __construct(LocalStorageDriver $localStorage)
    {
        $this->localStorage = $localStorage;
    }

    // The access function is the gateway to all the storage drivers
    public function access($storageLocation)
    {
        // Use a switch to determine which storage driver is needed
        switch (strtolower(...$storageLocation))
        {
            // Local storage driver for local filesystem access
            case 'local':

                return $this->localStorage;

                break;
        }

        // Return NULL when no storage driver match is found
        return null;
    }
}