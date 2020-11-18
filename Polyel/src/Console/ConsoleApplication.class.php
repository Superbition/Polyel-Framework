<?php

namespace Polyel\Console;

use Polyel;

class ConsoleApplication
{
    use InputMethods;
    use Polyel\View\ViewTools;

    private array $commands = [];

    private array $signatures = [];

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
     */
    public function command(string $signature)
    {
        // Only split the command name and its signature up if there is a space to indicate a signature...
        if(preg_match('/\s/', $signature))
        {
            // A command name and a signature is split up by a space
            [$commandName, $signature] = explode(' ', $signature, 2);

            $commandName = trim($commandName);
            $this->signatures[$commandName] = trim($signature);
        }
        else
        {
            // Else just get the command name and set the commands signature to null
            $commandName = $signature;
            $this->signatures[$commandName] = null;
        }

        $this->commands[] = $commandName;
    }

    private function parseCommandSignature($commandSignature)
    {
        // Return an empty parsed signature if one doesn't exist as a command may not have one
        if(is_null($commandSignature))
        {
            return [];
        }

        // Every command definition is defined between a '{ }'
        $commandDefinitions = $this->getStringsBetween($commandSignature, '{', '}');

        $parsedCommandSignature = [
            'arguments' => [],

            'options' => [
                'required' => [],
                'optional' => [],
            ],
        ];

        foreach($commandDefinitions as $commandDefinition)
        {
            // Process all command option definitions
            if($this->isAnOption($commandDefinition))
            {
                // An equals sign must always be present as it indicates if the option is required or not...
                if(strpos($commandDefinition, '=') !== false)
                {
                    $commandDefinition = explode('=', $commandDefinition);

                    // If no default is given after the = sign, it means the option is required
                    if(empty($commandDefinition[1]))
                    {
                        // Store the option as required
                        $parsedCommandSignature['options']['required'][] = $commandDefinition[0];

                        continue;
                    }

                    // Else it means the option has a default value and is optional
                    $parsedCommandSignature['options']['optional'][] = [
                        'name' => $commandDefinition[0],
                        'default' => $commandDefinition[1],
                    ];
                }
            }
            else
            {
                // As a starting point, an argument is always deemed required
                $argumentOptionality = 'required';

                // If an argument contains a question mark at the beginning it means the argument is optional...
                if($commandDefinition[0] === '?')
                {
                    // Set the optionality to optional and trim off the question mark from the left
                    $argumentOptionality = 'optional';
                    $commandDefinition = ltrim($commandDefinition, '?');
                }

                // An optional argument must be defined as optional and have a default value assigned using the = sign
                if($argumentOptionality === 'optional' && strpos($commandDefinition, '=') !== false)
                {
                    $commandDefinition = explode('=', $commandDefinition);

                    $parsedCommandSignature['arguments'][] = [
                        'Optionality' => $argumentOptionality,
                        'name' => $commandDefinition[0],
                        'default' => $commandDefinition[1],
                    ];

                    continue;
                }

                // At this stage it means we are dealing with a required argument, so we set the argument as required
                $parsedCommandSignature['arguments'][] = [
                    'Optionality' => $argumentOptionality,
                    'name' => $commandDefinition,
                    'default' => null,
                ];
            }
        }

        /*
         * Return an array of the parsed command signature, making it easier
         * to work with when validating the command input. This way we can
         * validate if required arguments are present and if optional
         * arguments are not etc.
         */
        return $parsedCommandSignature;
    }
}