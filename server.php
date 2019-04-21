<?php

require __DIR__ . "/phase/autoload.php";
require __DIR__ . "/phase/helperFunctions/helperFunctions.php";

$server = new Phase_Server();

$server->boot();

$server->setupReactors();

$server->run();