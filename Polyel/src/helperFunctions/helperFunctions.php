<?php

function dump($input = NULL)
{
    Debug::dump($input);
}

function env($envRequest, $defaultValue)
{
    return Polyel::call(Polyel\Config\Config::class)->env($envRequest, $defaultValue);
}