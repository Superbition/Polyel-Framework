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

        $status = $this->console->run(
            $input->command,
            $this->getCommandAction($input->command),
            $input->arguments,
            $input->options
        );

        if($status['code'] > 0)
        {
            fwrite(STDERR, "\e[41;1;33m[Fatal]\e[0m " . $status['message'] . "\n\n");
        }

        return $status['code'];
    }

    private function registerInternalcommandActions()
    {
        $this->commandActions = array_merge($this->commandActions, [

            'list' => Commands\ListCommand::class,

        ]);
    }

    public function getCommandAction($alias)
    {
        if(array_key_exists($alias, $this->commandActions))
        {
            return $this->commandActions[$alias];
        }

        return false;
    }
}