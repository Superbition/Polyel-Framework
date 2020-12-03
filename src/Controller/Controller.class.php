<?php

namespace Polyel\Controller;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Controller
{
    private $controllerDir = APP_DIR . "/app/Http/Controllers/";

    public function __construct()
    {

    }

    public function loadAllControllers()
    {
        $controllerDir = new RecursiveDirectoryIterator($this->controllerDir);
        $pathIterator = new RecursiveIteratorIterator($controllerDir);

        foreach($pathIterator as $controller)
        {
            $controllerFilePath = $controller->getPathname();

            if(preg_match('/^.+\.php$/i', $controllerFilePath))
            {
                require_once $controllerFilePath;
            }
        }
    }
}