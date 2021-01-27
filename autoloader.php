<?php

/*
 * The autoloader is used to load classes when they are dependant on other
 * classes which have not yet been defined. For example, when inheritance is used...
 */

use Polyel\System\Exceptions\ThirdPartyUnknownFileException;

spl_autoload_register(static function($fullClassNamespace)
{
    // List of directories to try and load required classes from
    $directoryRoots = [

        // The Polyel Source directory
        __DIR__ . '/src',

        // The application directory
        APP_DIR . '/app',

        // Third party Composer packages directory
        APP_DIR . '/vendor',

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
        // Used to check for classes located within a third party Composer package
        if($root === APP_DIR . '/vendor')
        {
            // Get the Composer auto-load classmap, we can use this to know where to load the class from
            $composerAutoloadClassmap = require(APP_DIR . '/vendor/composer/autoload_classmap.php');

            // The Composer classmap uses double slashes for namespaces e.g. Foo\\Bar\\Bin
            $composerCompatibleNamespace = str_replace("\/", "/\/\/", $fullClassNamespace);

            /*
             * If the missing requested class is apart of the Composer
             * classmap, it means we can use its array value to load
             * the missing class from the filesystem, when a class is
             * apart of this classmap array it means it is from a
             * Composer package.
             */
            if(isset($composerAutoloadClassmap[$composerCompatibleNamespace]))
            {
                // Double checking that the file does actually exist...
                if(file_exists($composerAutoloadClassmap[$composerCompatibleNamespace]))
                {
                    require_once $composerAutoloadClassmap[$composerCompatibleNamespace];

                    break;
                }
            }

            /*
             * Getting to this stage means we have tried to load a
             * third party Composer package class which is likely
             * only used for package development or testing and thus,
             * we don't need to load this because its not part of
             * production code. We throw an exception here so it is
             * handled by the ApplicationLoader.
             */
            throw new ThirdPartyUnknownFileException($fullClassNamespace);
        }

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