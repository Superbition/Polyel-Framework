<?php

require __DIR__ . "/phase/src/autoload.php";
require __DIR__ . "/phase/src/helperFunctions/helperFunctions.php";

$server = new Phase_Server();

$server->boot();

$server->setupReactors();

$server->run();