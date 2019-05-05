<?php

require __DIR__ . "/phase/bootstrap.php";

$server = new Phase_Server();

$server->boot();

$server->setupReactors();

$server->run();