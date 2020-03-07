<?php

namespace Polyel\Controller;

use Polyel;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Controller
{
    private $controllerDir = __DIR__ . "/../../../app/Controllers/";

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

                $listOfDefinedClasses = get_declared_classes();
                $definedClass = explode("\\", end($listOfDefinedClasses));
                $definedClass = end($definedClass);

                Polyel::resolveClass("App\Controllers\\" . $definedClass);
            }
        }
    }
}