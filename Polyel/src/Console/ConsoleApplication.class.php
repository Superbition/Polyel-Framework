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

    public function run(string $commandName, string $commandFQN, array $arguments, array $options): array
    {
        // The command fully qualified namespace will be false if one was not found
        if($commandFQN === false)
        {
            return ['code' => 1, 'message' => "The command $commandName has no registered command action."];
        }

        // The starting status is success and no message
        $status = ['code' => 0, 'message' => ''];

        // Make sure the command we want to run exists within the list of registered commands
        if(in_array($commandName, $this->commands, true))
        {
            $consoleCommand = Polyel::resolveClass($commandFQN);

            // Add core native/reserved options to the signature
            $commandSignature = $this->includeReservedOptions($this->signatures[$commandName]);

            $parsedCommandSignature = $this->parseCommandSignature($commandSignature);

            if(!empty($parsedCommandSignature))
            {
                // Match the command input to the command signature if a signature exists
                $validatedCommandInput = $this->checkCommandInputValidity($arguments, $options, $parsedCommandSignature);

                if($validatedCommandInput === false)
                {
                    $validatedCommandInput = [];
                }

                [$processedInputArguments, $processedInputOptions] = $validatedCommandInput;
            }
            else
            {
                [$processedInputArguments, $processedInputOptions] = [];
            }

            /*
             * Run the console command inside a coroutine but
             * catch any Swoole Exit Exceptions and return a proper console status code.
             */
            go(function() use($consoleCommand, &$status, $processedInputArguments, $processedInputOptions)
            {
                try
                {
                    $consoleCommand
                        ->useInput($processedInputArguments, $processedInputOptions)
                        ->setVerbosity($processedInputOptions['-v'], $processedInputOptions['-q'])
                        ->execute();
                }
                catch(\Swoole\ExitException $exception)
                {
                    fwrite(STDERR, 'Exception: ' . $exception->getStatus());

                    $status['code'] = 1;
                }
            });
        }

        return $status;
    }

    private function includeReservedOptions($signature)
    {
        // Add core native/reserved options to the signature
        return $signature . '{--v|verbostity=0} {--q|quiet=false} {--h|help=false}';
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
                $optionShortcut = false;

                // If a pipe symbol is present it means we have a defined short and long notation
                if(strpos($commandDefinition, '|') !== false)
                {
                    // Split up the command definition to get the short and long option separately
                    $commandDefinition = explode('|', $commandDefinition);

                    // Save the short option but remove the additional hyphen
                    $optionShortcut = '-' . ltrim($commandDefinition[0], '-');

                    // Save the long option as the main command definition
                    $commandDefinition = "--$commandDefinition[1]";
                }

                // An equals sign must always be present as it indicates if the option is required or not...
                if(strpos($commandDefinition, '=') !== false)
                {
                    $commandDefinition = explode('=', $commandDefinition);

                    /*
                     * When a shortcut option is set, include both the short and long
                     * notation as the command definition, splitting them up with a
                     * pipe symbol.
                     */
                    if($optionShortcut !== false)
                    {
                        $commandDefinition[0] = "$optionShortcut|$commandDefinition[0]";
                    }

                    // If no default is given after the = sign, it means the option is required
                    if(!exists($commandDefinition[1]))
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

    private function checkCommandInputValidity(array $inputArguments, array $inputOptions, array $commandSignature)
    {
        // A set of arrays to store valid command input that has been matched against a command signature
        $processedInputArguments = [];
        $processedInputOptions = [];

        /*
         * Process all the command arguments, figuring out which
         * are required and which are optional. Also if a default value
         * is given.
         */
        foreach($commandSignature['arguments'] as $key => $arg)
        {
            // Save a required argument if it is present and not empty
            if($arg['Optionality'] === 'required' && isset($inputArguments[$key]) && !empty($inputArguments[$key]))
            {
                $processedInputArguments[$arg['name']] = $inputArguments[$key];

                continue;
            }

            // Save a optional argument either using the given value or the default value
            if($arg['Optionality'] === 'optional')
            {
                if(!isset($inputArguments[$key]) && isset($arg['default']))
                {
                    $processedInputArguments[$arg['name']] = $arg['default'];
                }
                else if(isset($inputArguments[$key]))
                {
                    $processedInputArguments[$arg['name']] = $inputArguments[$key];
                }

                continue;
            }

            // Error: The argument is required and not present and not optional
            return false;
        }

        /*
         * Process all options that are defined as required, making
         * sure that they are present and not empty. Supports
         * both the short and long notation of option names. If both
         * notations are used, they're values are combined.
         */
        foreach($commandSignature['options']['required'] as $option)
        {
            // When checking for required options, it could be a short and or long notation that is used...
            if($options = $this->isShortOrLongOptionPresent($option, $inputOptions))
            {
                foreach($options['notations'] as $notation)
                {
                    // Even if both notations for short or long are used, they should still contain the same values
                    $processedInputOptions[$notation] = $options['values'];
                }

                continue;
            }

            // Error: THe option is required but is not present or is empty
            return false;
        }

        /*
         * Process all options that are defined as optional and
         * either using the given value or the default value if
         * not present but, the options value will be the same
         * regardless if both short or long notations were
         * used or not.
         */
        foreach($commandSignature['options']['optional'] as $option)
        {
            // Options can use a short and or long syntax, but we need to detect both notations
            if($options = $this->isShortOrLongOptionPresent($option['name'], $inputOptions, 'optional'))
            {
                foreach($options['notations'] as $notation)
                {
                    // Use the values provided if any have been set, otherwise use the optional default value
                    if(!isset($options['values']) && empty($options['values']))
                    {
                        // The default value that is assigned from the command definition
                        $processedInputOptions[$notation] = $option['default'];
                    }
                    else
                    {
                        // The values that were given from the command input
                        $processedInputOptions[$notation] = $options['values'];
                    }
                }
            }
        }

        return [$processedInputArguments, $processedInputOptions];
    }

    private function isShortOrLongOptionPresent(string $optionName, array $inputOptions, $optionality = 'required')
    {
        // A pipe symbol means we have short and long notations specified
        if(strpos($optionName, '|') !== false)
        {
            // Split up the option notations that are set
            $optionNotations = explode('|', $optionName);
        }
        else
        {
            $optionNotations = [$optionName];
        }

        // An array to store both short and long options and their values
        $shortOrLongOption = [];

        // Process each present option notations one at a time
        foreach($optionNotations as $notation)
        {
            // Always add a option notation even if it is not used, otherwise it won't get assigned any values
            $shortOrLongOption['notations'][] = $notation;

            if(isset($inputOptions[$notation]) && !empty($inputOptions[$notation]))
            {
                $shortOrLongOption['values'][] = $inputOptions[$notation];
            }
        }

        if(isset($shortOrLongOption['values']) && !empty($shortOrLongOption['values']))
        {
            // If our option values is an array we need to do some clean up...
            if(is_array($shortOrLongOption['values']))
            {
                // If we only have one value present, there is no need to use an array, flatten down to just the value
                if(count($shortOrLongOption['values']) === 1)
                {
                    $shortOrLongOption['values'] = $shortOrLongOption['values'][0];
                }
                else if(count($shortOrLongOption['values']) > 1)
                {
                    // We need to flatten the array to make it a single dimension as we have multiple values
                    $flatteredOptionValues = [];
                    array_walk_recursive($shortOrLongOption['values'], function($value) use(&$flatteredOptionValues)
                    {
                        $flatteredOptionValues[] = $value;
                    });

                    $shortOrLongOption['values'] = $flatteredOptionValues;
                }
            }

            return $shortOrLongOption;
        }

        // Return option notations defined for when optional option values have not been set
        if($optionality === 'optional')
        {
            // Optional options can use their default value if not set, but they still need their defined notations
            return $shortOrLongOption;
        }

        // No short or long option is present with any values for a required option
        return false;
    }
}