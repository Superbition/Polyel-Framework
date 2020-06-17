<?php

namespace Polyel\View\Element;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Element
{
    private const ELEMENT_CLASS_DIR = ROOT_DIR . "/app/View/Elements";

    public function __construct()
    {

    }

    public function processElementsFor(&$mainResource, $elementTags, $HttpKernel)
    {
        if(exists($elementTags))
        {
            foreach($elementTags as $element)
            {
                $elementClass = $HttpKernel->container->resolveClass("App\View\Elements\\" . $element);

                $renderedElement = $elementClass->build();

                $mainResource = str_replace("{{ @addElement($element) }}", $renderedElement, $mainResource);

                $elementClass->reset();
            }
        }
    }

    public static function loadClassElements()
    {
        $elementClassDir = new RecursiveDirectoryIterator(static::ELEMENT_CLASS_DIR);
        $pathIterator = new RecursiveIteratorIterator($elementClassDir);

        foreach($pathIterator as $elementClass)
        {
            $elementClassFilePath = $elementClass->getPathname();

            if(preg_match('/^.+\.php$/i', $elementClassFilePath))
            {
                require_once $elementClassFilePath;
            }
        }
    }
}