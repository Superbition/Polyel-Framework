<?php

spl_autoload_register("classLoader");

function classLoader($className)
{
    $classPath = __DIR__ . "/classes/" . $className . ".class.php";

    require $classPath;
}