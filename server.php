<?php

require __DIR__ . "/core/autoload.php";
require __DIR__ . "/core/helperFunctions/helperFunctions.php";

$server = new Phase_Server();

$server->boot();

$server->setupReactors();

$server->run();