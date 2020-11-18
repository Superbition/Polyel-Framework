<?php

namespace Polyel\Console;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

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

    private function defineConsoleCommands(string $directory)
    {
        $consoleDir = new RecursiveDirectoryIterator(ROOT_DIR . $directory);
        $consoleDir = new RecursiveIteratorIterator($consoleDir);

        foreach($consoleDir as $commandFile)
        {
            $commandFilePath = $commandFile->getPathname();

            if(preg_match('/^.+\.php$/i', $commandFilePath))
            {
                require_once $commandFilePath;
            }
        }
    }

    public function process(Input $input)
    {
        $this->defineConsoleCommands('/app/Console/Commands/');
        $this->defineConsoleCommands('/Polyel/src/Console/Commands/');

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