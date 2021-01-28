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
            $composerAutoloadClassmap = require APP_DIR . '/vendor/composer/autoload_classmap.php';

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
             * Sometimes when loading library code they will use PHP functions which
             * by default, call the registered autoloader and by doing that it means
             * the ThirdPartyUnknownFileException won't be within a Try Catch block.
             * When that happens we assume the library at question is trying to check
             * if a object of some kind exists and if it does, then do something about
             * it. So the safest option is to let the library handle the result and not
             * to throw an error about a missing object. We break out of the loop and
             * don't do anything about the missing object if it is not found before.
             *
             * Using a backtrace we can detect what function call caused the autoloader
             * to be called and if it is a native PHP function which by default calls
             * the autoloader we just ignore it if the object was not found through the
             * classmap. For performance reasons we don't include object back traces and
             * ignore arguments and finally limit by 3 as the 3rd element should always
             * be the caller which initiated the autoloader.
             */
            $trace = debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 3);
            if(isset($trace[2]) && $trace[2]['function'] === 'interface_exists')
            {
                // Let the library (which called the autoloader) handle the result of a missing class
                break;
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