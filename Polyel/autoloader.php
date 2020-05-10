<?php

/*
 * The autoloader is used to load classes when they are dependant on other
 * classes which have not yet been defined. For example, when inheritance is used...
 */
spl_autoload_register(static function($fullClassNamespace)
{
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

    // Add the src directory onto the front and the full class file extension
    $classFilePath = __DIR__ . "/src" . $classFilePath;
    $classFilePath .= ".class.php";

    // Check that the class now exists
    if(file_exists($classFilePath))
    {
        // Finally include the required class
        require $classFilePath;
    }

});