<?php

namespace Polyel\View\Element;

use Polyel;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Element
{
    private $elementClassDir = ROOT_DIR . "/app/View/Elements";

    private $elementTemplateDir = ROOT_DIR . "/app/resources/elements";

    public function __construct()
    {

    }

    public function loadClassElements()
    {
        $elementClassDir = new RecursiveDirectoryIterator($this->elementClassDir);
        $pathIterator = new RecursiveIteratorIterator($elementClassDir);

        foreach($pathIterator as $elementClass)
        {
            $elementClassFilePath = $elementClass->getPathname();

            if(preg_match('/^.+\.php$/i', $elementClassFilePath))
            {
                require_once $elementClassFilePath;

                $listOfDefinedClasses = get_declared_classes();
                $definedClass = explode("\\", end($listOfDefinedClasses));
                $definedClass = end($definedClass);

                Polyel::resolveClass("App\View\Elements\\" . $definedClass);
            }
        }
    }
}