<?php

echo "Bootstrap process started...\n";

$startingDirectory = new RecursiveDirectoryIterator(__DIR__ . "/src/");
$pathIterator = new RecursiveIteratorIterator($startingDirectory);

$traits = [];
$polyelSourceFiles = [];

echo "Building Polyel Framework source map\n";
foreach ($pathIterator as $file)
{
    $currentFile = $file->getPathname();

    // Build up a class/ source map of the Polyel Framework. Don't load the autoloader file.
    if(preg_match('/^.+\.php$/i', $currentFile) && !stristr($currentFile, "autoload.php"))
    {
        // Traits need to be loaded first, so collect them separately
        if(preg_match("/.trait.php/", strtolower($currentFile)))
        {
            // Traits will be merged to be placed at the start of the loading process later
            $traits[] = $currentFile;
            continue;
        }

        $polyelSourceFiles[] = $currentFile;
    }
}

// Put all traits at the start of the source map so they are loaded first.
$polyelSourceFiles = array_merge($traits, $polyelSourceFiles);

// Loop through each source file and load them in.
foreach ($polyelSourceFiles as $file)
{
    // Load each Polyel Framework core PHP file to make them available using the class map.
    if (file_exists($file))
    {
        // Use a green terminal colour.
        echo "\e[32m Loading: " . $file . "\n";
        require $file;
    }
    else
    {
        throw new Exception("ERROR: Missing framework source file: " . $file);
    }
}

// Reset terminal colour back to normal.
echo "\e[39m";

// Create the DIC and create a new Polyel HTTP Server instance as the base class
Polyel::createContainer(Polyel\Http\Server::class);