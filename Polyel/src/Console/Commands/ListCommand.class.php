<?php

namespace Polyel\Console\Commands;

use Polyel;
use App\Console\Kernel;
use Polyel\Console\Command;
use Polyel\Console\ConsoleApplication;

class ListCommand extends Command
{
    public string $description = 'Shows a list of available commands.';

    public function execute(Kernel $consoleKernel, ConsoleApplication $consoleApp, HelpCommand $help)
    {
        /*
         * List command title
         */
        $this->writeNewLine("\e[1;35mPolyel Console", 2);

        /*
         * Show general command usage format
         */
        $this->writeNewLine("\e[1;35mUsage: ");
        $this->writeNewLine('    command [options] [--] [arguments]', 2);

        /*
         * Show global options
         */
        $this->writeNewLine("\e[1;35mOptions: ");

        $commandSignature = $consoleApp->includeReservedOptions($consoleApp->getCommandSignatureFor('list'));
        $parsedCommandSignature = $consoleApp->parseCommandSignature($commandSignature);

        $optionDescription = '';
        $optionDescription .= $help->processOptionsIntoADescription($parsedCommandSignature['options']['required'], $parsedCommandSignature['descriptions']);
        $optionDescription .= $help->processOptionsIntoADescription($parsedCommandSignature['options']['optional'], $parsedCommandSignature['descriptions']);

        $this->writeNewLine($optionDescription, 2);

        /*
         * List all the available commands to the Polyel console
         */
        $this->writeNewLine("\e[1;35mAvailable Commands: ");

        $commandActions = $consoleKernel->getAllCommandActions();

        $commandList = [];
        $commandNamespacedList = [];

        foreach($commandActions as $commandName => $action)
        {
            $commandClass = Polyel::resolveClass($action);

            $commandName = explode(':', $commandName);

            if(count($commandName) > 1)
            {
                $commandNamespacedList[$commandName[0]][$commandName[1]] = $commandClass->description;
            }
            else
            {
                $commandList[$commandName[0]] = $commandClass->description;
            }
        }

        ksort($commandList, SORT_STRING);

        foreach($commandList as $command => $desc)
        {
            $spacer = (32 - strlen($command));

            $this->writeNewLine('    ' . $command . str_repeat(' ', $spacer) . $desc);
        }

        ksort($commandNamespacedList, SORT_STRING);

        foreach($commandNamespacedList as $namespace => $command)
        {
            $this->writeNewLine("\e[1;4m$namespace");

            foreach($command as $name => $desc)
            {
                $this->writeLine('    ' . "$namespace:$name");

                $spacer = (32 - strlen("$namespace:$name"));

                $this->writeNewLine(str_repeat(' ', $spacer) . $desc);
            }
        }

        $this->writeNewLine('');
    }
}