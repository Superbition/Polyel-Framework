<?php

namespace Polyel\Console;

class Kernel
{
    protected $console;

    protected string $defaultCommand = 'list';

    protected array $commandActions = [];

    public function __construct(ConsoleApplication $console)
    {
        $this->console = $console;
        $this->console->loadCommandsFrom('/routing/console.php');
        $this->console->loadCommandsFrom('/Polyel/src/Console/Commands/console.php');

        $this->registerInternalcommandActions();
    }

    public function process(Input $input)
    {
        $input->parseCommandInput();

        if(!exists($input->command))
        {
            $input->command = $this->defaultCommand;
        }
    }

    private function registerInternalcommandActions()
    {
        $this->commandActions = array_merge($this->commandActions, [

            'list' => Commands\ListCommand::class,

        ]);
    }
}