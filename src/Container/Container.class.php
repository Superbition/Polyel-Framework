<?php

namespace Polyel\Container;

use Closure;
use ReflectionClass;
use ReflectionFunction;

class Container
{
    use RegistersServices;

    // Holds all the registered class instances
    private array $container = [];

    private array $binds = [];

    private array $singletons = [];

    // Can be passed an array of starting classes to resolve and be placed in the container
    public function __construct($classesToResolve = null, $binds = [], $singletons = [])
    {
        /*
         * Register any starting objects defined as a bind so
         * they can be resolved by returning a new instance
         * each time they are requested and not from the container.
         */
        if(!empty($binds))
        {
            foreach($binds as $bind)
            {
                $this->bind($bind['class'], $bind['closure']);
            }
        }

        /*
         * Register any singletons that are defined as being deferred
         * until requested or if they should be resolved into the container
         * straight away because they are not defined as deferred.
         */
        if(!empty($singletons))
        {
            foreach($singletons as $singleton)
            {
                $this->singleton($singleton['class'], $singleton['closure'], $singleton['defer']);
            }
        }

        if(isset($classesToResolve))
        {
            if(!is_array($classesToResolve))
            {
                $classesToResolve = [$classesToResolve];
            }

            // Resolve each class inside the array that was passed in
            foreach($classesToResolve as $class)
            {
                // Each class is then stored inside the container
                $this->checkForDependencies($class);
            }
        }
    }

    // Recursively checks for class dependencies, resolves them and creates class instances.
    private function checkForDependencies($classToResolve, $returnClassOnly = false)
    {
        // Return when the class already exists inside the container
        if($this->get($classToResolve))
        {
            // Stops classes being overwritten again if called to be resolved
            return;
        }

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
                // Get the parameter type, returns NULL on not type
                $dependencyToCheck = $param->getType();

                // Pass onto the next paramater if the type is not defined e.g. could be a normal variable
                if(!isset($dependencyToCheck))
                {
                    continue;
                }

                // Only get the class name and not the type.
                $dependencyToCheck = $dependencyToCheck->getName();

                // For when the class has a dependency that does not exist yet.
                if(!$this->get($dependencyToCheck))
                {
                    // Recursively resolve and check further dependencies before we resolve the final class.
                    $resolvedClass = $this->checkForDependencies($dependencyToCheck, $returnClassOnly);
                }

                // Using the constructors parameters, we store all the required dependencies here.
                $dependencyList[] = $this->get($dependencyToCheck) ?? $resolvedClass;
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
        return $this->resolveClassDependency($classToResolve, $dependencyList, $returnClassOnly);
    }

    // Resolve a single class dependency and store it in the container
    private function resolveClassDependency($dependencyToResolve, $classArgs, $returnClassOnly)
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

        // Whether  to just return the class by itself or to place it inside the service container
        if($returnClassOnly)
        {
            // Return a fully resolved class
            return $newClassInstance;
        }
        else
        {
            // Finally store the newly created instance inside the container.
            $this->container[$dependencyToResolve] = $newClassInstance;
        }
    }

    // Public facing function to externally resolve a class
    public function resolveClass($classToResolve)
    {
        // Calling get here checks if the requested class is a bind or singleton object
        if($class = $this->get($classToResolve))
        {
            // Either a class is resolved from a bind or singleton or is already inside the container...
            return $class;
        }

        $this->checkForDependencies($classToResolve);

        return $this->get($classToResolve);
    }

    // Used to perform method injection within a class
    public function resolveMethodInjection($class, $methodToResolve)
    {
        // Get the class we want to perform method injection on
        $class = $this->get($class);

        // Check that the class does exist...
        if(isset($class))
        {
            // Reflect the class...
            $class = new ReflectionClass($class);

            // Check the class has the method we want to inject on
            if($class->hasMethod($methodToResolve))
            {
                // Get the methods parameters, if any...
                $methodParams = $class->getMethod($methodToResolve)->getParameters();

                // Loop through each method param and resolve them if needed
                $methodDependencyList = [];
                foreach($methodParams as $param)
                {
                    // Cannot process a parameter if it does not have a type
                    if(!$param->hasType())
                    {
                        // Skip current param because it does not have a type thus, cannot check what class it is
                        continue;
                    }

                    // Getting the name gets the full namespace
                    $methodDependencyName = $param->getType()->getName();
                    $methodDependency = $this->get($methodDependencyName);

                    // Sometimes the method param dependency might not exist yet, try to resolve it...
                    if(is_null($methodDependency))
                    {
                        // Try to resolve a class that may not have been initiated
                        $this->checkForDependencies($methodDependencyName);
                        $methodDependency = $this->get($methodDependencyName);
                    }

                    // Once the method dependency has been resolve, add it to the list to return later...
                    $methodDependencyList[] = $methodDependency;
                }

                // Return any method dependencies
                return $methodDependencyList;
            }
        }

        // Return false when the class we want to perform method inject on does not exist
        return false;
    }

    // Used to resolve a Closures parameter dependencies for a Closure
    public function resolveClosureDependencies($method)
    {
        $closureDependencyList = [];

        if($method instanceof Closure)
        {
            $closureReflection = new ReflectionFunction($method);

            $closureParameters = $closureReflection->getParameters();

            // Loop through each Closure param and resolve them if needed
            foreach($closureParameters as $param)
            {
                // Cannot process a parameter if it does not have a type
                if(!$param->hasType())
                {
                    // Skip current param because it does not have a type thus, cannot check what class it is
                    continue;
                }

                // Getting the name gets the full namespace
                $closureDependencyName = $param->getType()->getName();
                $closureDependency = $this->get($closureDependencyName);

                // Sometimes the Closure param dependency might not exist yet, try to resolve it...
                if(is_null($closureDependency))
                {
                    // Try to resolve a class that may not have been initiated
                    $this->checkForDependencies($closureDependencyName);
                    $closureDependency = $this->get($closureDependencyName);
                }

                // Once the Closure dependency has been resolve, add it to the list to return later...
                $closureDependencyList[] = $closureDependency;
            }
        }

        // Return any Closure dependencies
        return $closureDependencyList;
    }

    /*
     * Used to check if a requested class is a resolvable
     * bind object. The class is resolved if it is registered
     * as a bind object and returned.
     */
    private function resolvableBindObject(string $classToResolve)
    {
        if(isset($this->binds[$classToResolve]))
        {
            // Call the bind objects closure to resolve its instance
            return $this->binds[$classToResolve]($this);
        }

        // No, is not a bind object
        return false;
    }

    /*
     * Used to check if the requested class is a resolvable
     * singleton object that has been defined as deferred. The
     * class is resolved from the singleton closure, removed
     * from the list of singletons and returned. Also store the
     * resolved singleton inside the container.
     */
    private function resolvableDeferredSingletonObject(string $classToResolve)
    {
        if(isset($this->singletons[$classToResolve]))
        {
            // Call the registered closure to resolve the singleton and its instance
            $resolvedSingleton = $this->singletons[$classToResolve]($this);

            // Because we have resolved the object, it doesn't need to be stored anymore
            unset($this->singletons[$classToResolve]);

            // The resolved singleton is now stored inside the container
            $this->container[$classToResolve] = $resolvedSingleton;

            return $resolvedSingleton;
        }

        // No, is not a singleton object
        return false;
    }

    /*
     * Used to retrieve class instances from the container but
     * will also check if the requested class is registered as a
     * bind or singleton object first before trying to retrieve
     * the class from the container.
     */
    public function get($className)
    {
        // Check if the requested class is a bind object...
        if($resolvedBind = $this->resolvableBindObject($className))
        {
            // A bind means the instance is recreated every time, so we return a new instance
            return $resolvedBind;
        }

        // Check if the requested class is listed as a deferred singleton...
        if($resolvedSingleton = $this->resolvableDeferredSingletonObject($className))
        {
            // Return a resolved singleton because it was defined as deferred and is now stored within the container
            return $resolvedSingleton;
        }

        // Return a class instance if it exists inside the container
        if(array_key_exists($className, $this->container))
        {
            return $this->container[$className];
        }

        // For when the requested class does not exist inside the container or cannot be resolved properly...
        return null;
    }

    // Used to resolve and create a new class without storing it inside the container
    public function new($class)
    {
        // Returns a fully resolved class by itself and does not place it inside the service container
        return $this->checkForDependencies($class, true);
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