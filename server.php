<?php

require __DIR__ . "/phase/bootstrap.php";

$server = new Polyel_Server();

$server->boot();

$server->registerReactors();

$server->run();