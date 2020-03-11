<?php

echo "Creating Root Directory constant\n";
define("ROOT_DIR", __DIR__);

require __DIR__ . "/Polyel/bootstrap.php";

$server = Polyel::call(Polyel\Http\Server::class);

$server->boot();

$server->registerReactors();

$server->run();