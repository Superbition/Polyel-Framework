<?php

function dump($input = NULL)
{
    Debug::dump($input);
}

function env($envRequest, $defaultValue)
{
    return Config::env($envRequest, $defaultValue);
}