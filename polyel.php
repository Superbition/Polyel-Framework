#!/usr/bin/env php

<?php

define('ROOT_DIR', __DIR__);

require ROOT_DIR . '/Polyel/console-bootstrap.php';

$kernel = Polyel::newConsoleKernel();

$status = $kernel->process(new Polyel\Console\Input($argv, $argc));

exit($status);