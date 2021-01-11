<?php

namespace Polyel\Container;

use Closure;

trait RegistersServices
{
    /*
     * Bind a class to the container, meaning when a class is requested
     * the container will check if the class is a service bind before trying
     * to resolve the class by using its fully qualified namespace. A service
     * bind means that a new object is created every time it is requested from
     * the container using its service supplier closure.
     */
    public function bind(string $classToBind, Closure $classServiceSupplier)
    {
        $this->binds[$classToBind] = $classServiceSupplier;
    }

    /*
     * Register a class as a singleton to the container, meaning a class is
     * declared and is persistent across a the lifetime of the container.
     * A singleton can also be set to be deferred and only declared when it
     * is first used before storing it in the container. Also Keep a list of
     * objects which have been defined as shareable, meaning they can be shared
     * with another container.
     */
    public function singleton(string $singletonClass, Closure $singletonServiceSupplier, $defer = false, $shareable = false)
    {
        if($defer)
        {
            $this->deferredSingletons[$singletonClass] = $singletonServiceSupplier;
        }
        else
        {
            $this->container[$singletonClass] = $singletonServiceSupplier($this);
        }

        if($shareable)
        {
            $this->shareableObjects[] = $singletonClass;
        }
    }
}