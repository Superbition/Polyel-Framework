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

        // Making sure the class is not static.
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

            /*
             * Finally, we resolve the class, passing in any arguments the constructor requires.
             * The $dependencyList contains the class arguments in the form of a array.
             */
            $this->resolveClassDependency($classToResolve, $dependencyList);
        }
    }

    // Resolve a single class dependency and store it in the container
    private function resolveClassDependency($dependencyToResolve, $classArgs)
    {
        // Using Reflection, load the class up...
        $classDependency = new ReflectionClass($dependencyToResolve);

        // Crate an instance using and pass in any constructor arguments.
        $newClassInstance = $classDependency->newInstanceArgs($classArgs);

        // Finally store the newly created instance inside the container.
        $this->container[$dependencyToResolve] = $newClassInstance;
    }

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
}