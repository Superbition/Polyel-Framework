<?php

/*
 * The autoloader is used to load classes when they are dependant on other
 * classes which have not yet been defined. For example, when inheritance is used...
 */
spl_autoload_register(static function($fullClassNamespace)
{
    // List of directories to try and load required classes from
    $directoryRoots = [

        // The Polyel Source directory
        __DIR__ . '/src',

        // The application directory
        __DIR__ . '/../app',

    ];

    // Segment the path so we can turn it into a normal file path
    $classNamespaceSegmented = explode("\\", $fullClassNamespace);

    // Remove the Polyel segment from the namespace and reset the array index
    unset($classNamespaceSegmented[0]);
    $classNamespaceSegmented = array_values($classNamespaceSegmented);

    // Loop through the segmented namespace...
    $classFilePath = "";
    foreach($classNamespaceSegmented as $pathSegment)
    {
        // Build up the file path to the class using the namespace segments
        $classFilePath .= "/" . $pathSegment;
    }

    foreach($directoryRoots as $root)
    {
        // The class we want to try and find
        $class = $root . $classFilePath;

        // Check that the class exists before loading the file
        if(file_exists($class . '.class.php'))
        {
            require $class . '.class.php';

            // Class has been found, break out of the loop
            break;
        }

        // Check that the trait exists before loading the file
        if(file_exists($class . '.trait.php'))
        {
            require $class . '.trait.php';

            // Class has been found, break out of the loop
            break;
        }

        // Check if the class exists without an extension type like .class or .trait etc.
        if(file_exists($class . '.php'))
        {
            require $class . '.php';

            // Class has been found, break out of the loop
            break;
        }
    }

});