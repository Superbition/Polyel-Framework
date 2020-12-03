<?php

namespace Polyel\View\Element;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class Element
{
    private const ELEMENT_CLASS_DIR = APP_DIR . "/app/View/Elements";

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

                // If the build method returns false, it means we don't want this element in our final View
                if($renderedElement === false)
                {
                    // The element has returned without content, replace the element tag with nothing...
                    $renderedElement = "";
                }

                $mainResource = str_replace("{{ @addElement($element) }}", $renderedElement, $mainResource);
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