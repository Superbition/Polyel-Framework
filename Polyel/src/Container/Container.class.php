<?php

namespace Polyel\Container;

use ReflectionClass;

class Container
{
    // Holds all the registered class instances
    private $container = [];

    // Container Constructor. Can be passed a starting class to resolve as a base class.
    public function __construct($baseClass = null)
    {
        if(isset($baseClass))
        {
            $this->checkForDependencies($baseClass);
        }
    }

    // Recursively checks for class dependencies, resolves them and creates class instances.
    private function checkForDependencies($classToResolve)
    {
        // Using Reflection, load the class up...
        $classReflection = new ReflectionClass($classToResolve);

        // Get the class constructor method...
        $constructor = $classReflection->getConstructor();

        // A constructor is required to perform constructor dependency injection.
        if(isset($constructor))
        {
            // Collect all the constructor parameters, then we know the class requirements...
            $constructorParameters = $constructor->getParameters();

            // Used to store all the requested class dependencies.
            $dependencyList = [];

            // Loop through each constructor parameter and check if it exists in the container already.
            foreach($constructorParameters as $param)
            {
                // Only get the class name and not the type.
                $dependencyToCheck = $param->getType()->getName();

                // For when the class has a dependency that does not exist yet.
                if(!$this->get($dependencyToCheck))
                {
                    // Recursively resolve and check further dependencies before we resolve the final class.
                    $this->checkForDependencies($dependencyToCheck);
                }

                // Using the constructors parameters, we store all the required dependencies here.
                $dependencyList[] = $this->get($dependencyToCheck);
            }
        }
        else
        {
            // No constructor means we don't need any class arguments/ dependencies to create a new instance
            $dependencyList = null;
        }

        /*
         * Finally, we resolve the class, passing in any arguments (or not) to the
         * constructor with what it requires in order to initiate a new class.
         * The $dependencyList contains the class arguments in the form of a array.
         */
        $this->resolveClassDependency($classToResolve, $dependencyList);
    }

    // Resolve a single class dependency and store it in the container
    private function resolveClassDependency($dependencyToResolve, $classArgs)
    {
        // Using Reflection, load the class up...
        $classDependency = new ReflectionClass($dependencyToResolve);

        if(isset($classArgs))
        {
            // Crate an instance using and passing in any constructor arguments.
            $newClassInstance = $classDependency->newInstanceArgs($classArgs);
        }
        else
        {
            // No constructor, means no constructor dependency injection.
            $newClassInstance = $classDependency->newInstanceWithoutConstructor();
        }

        // Finally store the newly created instance inside the container.
        $this->container[$dependencyToResolve] = $newClassInstance;
    }

    // Public facing function to externally resolve a class
    public function resolveClass($classToResolve)
    {
        $this->checkForDependencies($classToResolve);

        return $this->get($classToResolve);
    }

    // Used to retrieve class instances from the container.
    public function get($className)
    {
        // Return a class instance if it exists inside the container
        if(array_key_exists($className, $this->container))
        {
            return $this->container[$className];
        }

        // For when the requested class does not exist inside the container...
        return null;
    }

    // A function to return all the names of the classes inside the container, full namespace is returned.
    public function list()
    {
        // Loop through to get all the FQNS for each item inside the container.
        $containerList = [];
        foreach ($this->container as $className => $object)
        {
            $containerList[] = $className;
        }

        return $containerList;
    }
}