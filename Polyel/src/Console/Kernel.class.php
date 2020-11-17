<?php

namespace Polyel\Console;

class Kernel
{
    protected $console;

    protected $defaultCommand = 'list';

    public function __construct(ConsoleApplication $console)
    {
        $this->console = $console;
        $this->console->loadCommandsFrom('/routing/console.php');
    }

    public function process(Input $input)
    {
        $input->parseCommandInput();
    }
}