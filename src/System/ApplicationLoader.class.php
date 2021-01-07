<?php

namespace Polyel\System;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

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
}