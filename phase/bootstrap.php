<?php

echo "Bootstrap process started...\n";

$startingDirectory = new RecursiveDirectoryIterator(__DIR__ . "/src/");
$pathIterator = new RecursiveIteratorIterator($startingDirectory);
$phaseSourceFiles = [];

echo "Building Phase Framework source map\n";
foreach ($pathIterator as $file)
{
    $currentFile = $file->getPathname();

    // Build up a class/ source map of the Phase Framework. Don't load the autoloader file.
    if(preg_match('/^.+\.php$/i', $currentFile) && !stristr($currentFile, "autoload.php"))
    {
        $phaseSourceFiles[] = $currentFile;
    }
}

foreach ($phaseSourceFiles as $file)
{
    // Load each Phase Framework core PHP file to make them available using the class map.
    if (file_exists($file))
    {
        echo "Loading " . $file . "\n";
        require $file;
    }
    else
    {
        throw new Exception("ERROR: Missing framework source file: " . $file);
    }
}