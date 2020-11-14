<?php

namespace Polyel\Console;

class ConsoleApplication
{
    private array $commands = [];

    private string $lastAddedCommand = '';

    private array $signatures = [];

    private array $actions = [];

    public function __construct()
    {

    }

    public function loadCommandsFrom(string $path)
    {
        require_once ROOT_DIR . $path;
    }

    /**
     * Register commands with the console application.
     *
     * @param string $signature
     *
     * @return $this
     */
    public function command(string $signature)
    {
        [$commandName, $signature] = explode(' ', $signature, 2);

        $commandName = trim($commandName);

        $this->commands[] = $commandName;
        $this->lastAddedCommand = $commandName;
        $this->signatures[$commandName] = trim($signature);

        return $this;
    }

    /**
     * Link an action to the last registered console command.
     *
     * @param mixed $action
     */
    public function action($action)
    {
        if(!empty($this->lastAddedCommand))
        {
            $this->actions[$this->lastAddedCommand] = $action;

            $this->lastAddedCommand = '';
        }
    }
}