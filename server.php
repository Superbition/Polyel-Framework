<?php

require __DIR__ . "/Polyel/bootstrap.php";

$server = new Polyel_Server();

$server->boot();

$server->registerReactors();

$server->run();