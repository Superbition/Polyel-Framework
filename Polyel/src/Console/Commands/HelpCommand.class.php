<?php

namespace Polyel\Console\Commands;

use Polyel;
use App\Console\Kernel;
use Polyel\Console\Command;
use Polyel\Console\ConsoleApplication;

class HelpCommand extends Command
{
    public string $description = 'Displays a help message for a given command';

    public function execute(Kernel $consoleKernel, ConsoleApplication $consoleApp)
    {
        /*
         * Show the command we are displaying help for
         */
        $command = $this->argument('command');

        $commandFQN = $consoleKernel->getCommandAction($command);

        $commandInstance = Polyel::resolveClass($commandFQN);

        $this->writeNewLine("\e[1;35mCommand: ");

        $this->writeNewLine('    ' . $command, 2);

        /*
         * Show the command description
         */
        $this->writeNewLine("\e[1;35mDescription: ");

        $this->writeNewLine('    ' . $commandInstance->description, 2);

        /*
         * Show the overall command usage with positional arguments
         */
        $this->writeNewLine("\e[1;35mUsage: ");

        $commandSignature = $consoleApp->includeReservedOptions($consoleApp->getCommandSignatureFor($command));
        $parsedCommandSignature = $consoleApp->parseCommandSignature($commandSignature);

        $optionUsage = '';

        if(isset($parsedCommandSignature['options']['required']) || isset($parsedCommandSignature['options']['optional']))
        {
            if(!empty($parsedCommandSignature['options']['required']) || !empty($parsedCommandSignature['options']['optional']))
            {
                $optionUsage = " [options] ";

                if(!empty($parsedCommandSignature['arguments']))
                {
                    $optionUsage .= '[--]';
                }
            }
        }

        $argumentUsage = '';
        $argumentsAndDescriptions = [];

        foreach($parsedCommandSignature['arguments'] as $argument)
        {
            if($argument['Optionality'] === 'required')
            {
                $argumentUsage .= '(';
            }

            $argumentUsage .= "<$argument[name]";

            if(!is_null($argument['default']))
            {
                $argumentUsage .= '=' . $argument['default'];
            }

            $argumentUsage .= '>';

            if($argument['Optionality'] === 'required')
            {
                $argumentUsage .= ')';
            }

            $argumentUsage .= ' ';

            if(isset($parsedCommandSignature['descriptions'][$argument['name']]))
            {
                $argumentsAndDescriptions[$argument['name']] = $parsedCommandSignature['descriptions'][$argument['name']];
            }
        }

        $argumentUsage = ' ' . trim($argumentUsage);

        $command .= $optionUsage . $argumentUsage;

        $this->writeNewLine('    ' . $command, 2);

        /*
         * Show the different arguments and their descriptions
         */
        $this->writeNewLine("\e[1;35mArguments: ");

        foreach($argumentsAndDescriptions as $name => $desc)
        {
            $spacer = (32 - strlen($name));

            $this->writeNewLine('    ' . "\e[90m" . $name . str_repeat(' ', $spacer) . "\e[0m" . $desc);
        }

        $this->writeNewLine('');

        /*
         * Display and list all the options and their notations with descriptions
         */
        $this->writeNewLine("\e[1;35mOptions: ");

        $optionDescription = '';

        $optionDescription .= $this->processOptionsIntoADescription($parsedCommandSignature['options']['required'], $parsedCommandSignature['descriptions']);

        $optionDescription .= $this->processOptionsIntoADescription($parsedCommandSignature['options']['optional'], $parsedCommandSignature['descriptions']);

        $this->writeNewLine($optionDescription, 2);
    }

    public function processOptionsIntoADescription($options, $optionDescriptions)
    {
        $description = '';

        foreach($options as $option)
        {
            if(strpos($option['name'], '|') !== false)
            {
                $optionNames = explode('|', $option['name']);

                $description .= '    ' . "\e[90m"  . $optionNames[0] . '  ' . $optionNames[1];

                if(isset($option['default']) && $option['default'] !== false && $option['default'] !== 'false')
                {
                    $description .= "=$option[default]";
                    $optionNames[1] .= "=$option[default]";
                }

                $description .= "\e[0m";

                if(isset($optionDescriptions[$optionNames[0]]))
                {
                    $spacer = (32 - strlen($optionNames[0] . '  ' . $optionNames[1]));

                    $description .= str_repeat(' ', $spacer) . $optionDescriptions[$optionNames[0]];
                }

                $description .= "\n";
            }
            else
            {
                $description .= '    ' . "\e[90m" . $option['name'];

                if(isset($option['default']) && $option['default'] !== false && $option['default'] !== 'false')
                {
                    $description .= "=$option[default]";
                    $option['name'] .= "=$option[default]";
                }

                $description .= "\e[0m";

                if(isset($optionDescriptions[$option['name']]))
                {
                    $spacer = (32 - strlen($option['name']));

                    $description .= str_repeat(' ', $spacer) . $optionDescriptions[$option['name']];
                }

                $description .= "\n";
            }
        }

        return $description;
    }
}