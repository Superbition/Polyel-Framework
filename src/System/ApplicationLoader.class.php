<?php

namespace Polyel\System;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Polyel\System\Exceptions\ThirdPartyUnknownFileException;

class ApplicationLoader
{
    public function __construct()
    {

    }

    public function load()
    {
        $this->loadElementLogicClasses();
        $this->loadServices();
        $this->loadMiddleware();
        $this->loadControllers();
    }

    public function loadOnly(array $inclusions)
    {
        if(in_array('elements', $inclusions, true))
        {
            $this->loadElementLogicClasses();
        }

        if(in_array('services', $inclusions, true))
        {
            $this->loadServices();
        }

        if(in_array('middleware', $inclusions, true))
        {
            $this->loadMiddleware();
        }

        if(in_array('controllers', $inclusions, true))
        {
            $this->loadControllers();
        }
    }

    private function loadDirectory($directory)
    {
        $recursiveDirectory = new RecursiveDirectoryIterator($directory);
        $directoryPathIterator = new RecursiveIteratorIterator($recursiveDirectory);

        foreach($directoryPathIterator as $directoryPath)
        {
            $directoryFilePath = $directoryPath->getPathname();

            if(preg_match('/^.+\.php$/i', $directoryFilePath))
            {
                require_once $directoryFilePath;
            }
        }
    }

    private function loadServices()
    {
        $this->loadDirectory(APP_DIR . '/app/Services/');
    }

    private function loadMiddleware()
    {
        $this->loadDirectory(APP_DIR . '/app/Http/Middleware/');
    }

    private function loadControllers()
    {
        $this->loadDirectory(APP_DIR . '/app/Http/Controllers/');
    }

    private function loadElementLogicClasses()
    {
        $this->loadDirectory(APP_DIR . '/app/View/Elements/');
    }

    public function loadThirdPartyPackages()
    {
        $composerClassmapFileLocation = APP_DIR . '/vendor/composer/autoload_classmap.php';

        // Only regenerate the Composer classmap automatically if set to do so
        if(config('main.autoGenerateComposerClassmap') === true)
        {
            // We need to check if Composer is available, if not, we don't need to try and load third part packages
            exec('su $(logname) -c "composer about"', $output, $composerGlobalCheckExitCode);
            if($composerGlobalCheckExitCode > 0 && !file_exists(APP_DIR . '/composer.phar'))
            {
                echo "Could not find usable Composer binary, not loading third part packages\n";
                return 1;
            }

            echo "\nPreparing to preload and define third party Composer packages\n";

            // Making sure the Composer classmap that will be used to preload packages is up-to-date
            echo "Regenerating Composer classmap, will be used to load Composer packages...\n";
            exec('su $(logname) -c "composer dumpautoload -o"', $output, $composerDumpExitCode);

            // Make sure that the Composer classmap was generated and exists for us to use
            echo "Checking that the Composer classmap exists... ";
            if(!file_exists($composerClassmapFileLocation))
            {
                echo "\n";
                return 2;
            }

            echo "It does!\n";
        }

        // Getting the classmap array so we can use it to preload each class
        echo "Attempting to grab the Composer autoload classmap...\n";
        $composerAutoloadClassmap = require $composerClassmapFileLocation;

        // The classmap should be an array and have one or more elements for it to be usable
        if(!is_array($composerAutoloadClassmap) && count($composerAutoloadClassmap) === 0)
        {
            echo "Composer classmap was found but no packages to preload, skipping preload process...\n";

            // We have a Composer classmap but there are no packages to preload
            return 3;
        }

        /*
         * At this stage, it means we have an up-to-date Composer classmap and
         * can now use that classmap array to preload all of the packages and
         * dependencies managed by Composer. This means all third party packages
         * will be available to the application from the beginning and during a
         * request, the classmap or auto-loading functions won't need to be
         * contacted, everything will already be in memory ready to use.
         */
        echo "Loading Composer packages...\n";
        foreach($composerAutoloadClassmap as $sourceFileToLoad)
        {
            try
            {
                require_once $sourceFileToLoad;
            }
            catch(ThirdPartyUnknownFileException $thirdPartyUnknownFileException)
            {
                /*
                 * If we reach this, it means we have likely tried to
                 * load a class which is only supposed to be used
                 * within a package development context or is a class
                 * only supposed to be used when testing. Because of
                 * how traditional Composer auto-loading works, classes
                 * are loaded when requested but, here we are loading all
                 * classes listed in the classmap. By doing this it means
                 * the classmap sometimes contains classes only supposed to
                 * be used in packages development or testing, so the safest
                 * option is to catch the fatal unknown class error here
                 * and just continue, relying on the classmap generated by
                 * Composer to have all the valid classes needed for
                 * production.
                 */
                continue;
            }
        }

        // Successful, all packages loaded without any problems
        return 0;
    }
}