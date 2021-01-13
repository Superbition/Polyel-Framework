<?php

use Swoole\Runtime;
use Polyel\Storage\Storage;
use Polyel\Hashing\Facade\Hash;
use Polyel\Encryption\Facade\Crypt;

// Services and functionality that is not needed when running console commands
$exclusions = [
    'Auth/Middleware/',
    'Controller/',
    'Console/Commands/console.php',
    'Router/',
    'Session/',
    'Http/Middleware/',
];

$startingDirectory = new RecursiveDirectoryIterator(__DIR__ . "/src/");
$pathIterator = new RecursiveIteratorIterator($startingDirectory);

$traits = [];
$interfaces = [];
$polyelSourceFiles = [];

foreach($pathIterator as $file)
{
    $currentFile = $file->getPathname();

    // Check each exclusion and skip the current file if it matches any exclusions
    foreach($exclusions as $exclusion)
    {
        if(strpos($currentFile, "src/$exclusion") !== false)
        {
            continue 2;
        }
    }

    // Build up a class/ source map of the Polyel Framework.
    if(preg_match('/^.+\.php$/i', $currentFile))
    {
        // Traits need to be loaded first, so collect them separately
        if(preg_match("/.trait.php/", strtolower($currentFile)))
        {
            // Traits will be merged to be placed at the start of the loading process later
            $traits[] = $currentFile;
            continue;
        }

        // Interfaces need to be loaded first, so collect them separately
        if(preg_match("/.interface.php/", strtolower($currentFile)))
        {
            // Interfaces will be merged to be placed at the start of the loading process later
            $interfaces[] = $currentFile;
            continue;
        }

        $polyelSourceFiles[] = $currentFile;
    }
}

require "autoloader.php";

// Put all interfaces at the start of the source map so they are available first
$polyelSourceFiles = array_merge($interfaces, $polyelSourceFiles);

// Put all traits at the start of the source map so they are loaded first.
$polyelSourceFiles = array_merge($traits, $polyelSourceFiles);

// Loop through each source file and load them in.
foreach($polyelSourceFiles as $file)
{
    // Load each Polyel Framework core PHP file to make them available using the class map.
    if(file_exists($file))
    {
        // Use a green terminal colour. Reset the terminal style at the end
        //echo "\e[32m Loading: " . $file . "\n" . "\e[39m";

        /*
         * Each class uses a NS which follows the file path, we need the segmented path to grab the NS of a class.
         * We can then use the FQNS to check to see if the class has already been autoloaded.
         */
        $filePathSegmented = explode("/", $file);

        // The src segment is the /src/ directory, this is used to detect when we reach NS level
        $srcSegmentFound = false;

        // Loop through the segmented file path and grab the FQNS...
        $classNamespace = "";
        foreach($filePathSegmented as $pathSegment)
        {
            // Detect when we reach the NS level of the file path
            if($pathSegment === "src")
            {
                // Reached NS file path level, skip using the /src/ segment
                $srcSegmentFound = true;
                continue;
            }

            // If NS level has been reached, collect NS segment
            if($srcSegmentFound)
            {
                // Build up the NS from the file path segment
                $classNamespace .= "\\" . $pathSegment;
            }
        }

        /*
         * Add Polyel onto the start of the NS and explode based on the file extension to have NS and file .ext
         * [0] - Will always be the NS
         * [1] - Will be the first section of the file .ext like '.class' for example
         * We only need [0] and [1]
         */
        $classNamespace = "\Polyel" . $classNamespace;
        $classNamespace = explode(".", $classNamespace);

        // Detect that the file type is a class and see if it has already been defined
        if($classNamespace[1] === "class" && class_exists($classNamespace[0], false))
        {
            // Class was defined by the autoloader, output message and bypass trying to load the class again...
            //echo "\e[33m     └---> Autoloaded: " . $classNamespace[0] . "\n" . "\e[39m";
        }
        else if($classNamespace[1] === "trait" && trait_exists($classNamespace[0], false))
        {
            // Class was defined by the autoloader, output message and bypass trying to load the class again...
            //echo "\e[33m     └---> Autoloaded: " . $classNamespace[0] . "\n" . "\e[39m";
        }
        else
        {
            // The file is either not a class or the class has not yet been defined.
            require $file;
        }
    }
    else
    {
        throw new Exception("ERROR: Missing framework source file: " . $file);
    }
}

Polyel::createContainer($coreConsoleServices);

Polyel::call(\Polyel\Config\Config::class)->load();

Crypt::setup();

Hash::setup();

Storage::setup();

Runtime::enableCoroutine();