<?php

namespace Polyel\Console;

class Kernel
{
    protected $console;

    protected string $defaultCommand = 'list';

    protected array $commandAliases = [];

    public function __construct(ConsoleApplication $console)
    {
        $this->console = $console;
        $this->console->loadCommandsFrom('/routing/console.php');
        $this->console->loadCommandsFrom('/Polyel/src/Console/Commands/console.php');

        $this->registerInternalCommandAliases();
    }

    public function process(Input $input)
    {
        $input->parseCommandInput();
    }

    private function registerInternalCommandAliases()
    {
        $this->commandAliases = array_merge($this->commandAliases, [

            'list' => Commands\ListCommand::class,

        ]);
    }
}