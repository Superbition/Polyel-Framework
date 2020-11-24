<?php

namespace Polyel\Console;

use Co;
use Polyel;
use RuntimeException;
use Swoole\Coroutine\WaitGroup;

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

            $commandDependencies = Polyel::resolveClassMethod($commandFQN, 'execute');

            // Add core native/reserved options to the signature
            $commandSignature = $this->includeReservedOptions($this->signatures[$commandName]);

            $parsedCommandSignature = $this->parseCommandSignature($commandSignature);

            /*
             * Only continue processing and matching up
             * command inputs to the command definition
             * if the parsed signature is not empty and
             * that no errors are set.
             */
            if(!empty($parsedCommandSignature) && !isset($parsedCommandSignature['error']))
            {
                // Match the command input to the command signature if a signature exists
                $validatedCommandInput = $this->checkCommandInputValidity($arguments, $options, $parsedCommandSignature);

                if(isset($validatedCommandInput['status']) && $validatedCommandInput['status'] === false)
                {
                    return ['code' => 1, 'message' => $validatedCommandInput['error']];
                }

                [$processedInputArguments, $processedInputOptions] = $validatedCommandInput;
            }
            else if(isset($parsedCommandSignature['error']))
            {
                return ['code' => 1, 'message' => $parsedCommandSignature['error']];
            }
            else
            {
                [$processedInputArguments, $processedInputOptions] = [];
            }

            /*
             * Create a new coroutine context container.
             * This allows us to wait for the command status response before
             * we continue and return control back to the console kernel.
             */
            Co\Run(function() use($consoleCommand, $commandDependencies, &$status, $processedInputArguments, $processedInputOptions)
            {
                // A new coroutine waiting group
                $commandWaitGroup = new WaitGroup();

                /*
                 * Run the console command inside a coroutine but
                 * catch any Swoole Exit Exceptions and return a proper console status code.
                 */
                go(function() use($consoleCommand, $commandDependencies, $commandWaitGroup, &$status, $processedInputArguments, $processedInputOptions)
                {
                    $commandWaitGroup->add();

                    try
                    {
                        $consoleCommand
                            ->useInput($processedInputArguments, $processedInputOptions)
                            ->setVerbosity($processedInputOptions['-v'], $processedInputOptions['-q'])
                            ->execute(...$commandDependencies);
                    }
                    catch(\Swoole\ExitException $exception)
                    {
                        fwrite(STDERR, 'Exit Exception: ' . $exception->getStatus());

                        $status['code'] = 1;
                    }
                    catch(RuntimeException $exception)
                    {
                        fwrite(STDERR, $exception->getMessage());

                        $status['code'] = 1;
                    }

                    $commandWaitGroup->done();
                });

                // Wait until the command inside the coroutine has completed before we continue
                $commandWaitGroup->wait();
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
                // If a pipe symbol is present it means we have a defined short and long notation
                if(strpos($commandDefinition, '|') !== false)
                {
                    // By default options are always deemed as optional first
                    $requiredDefinition = '';

                    if($commandDefinition[0] === '!')
                    {
                        $commandDefinition = ltrim($commandDefinition, '!');

                        /*
                         * An ! means the option is defined as required, this
                         * goes at the start of an option in front of
                         * any option hyphens.
                         */
                        $requiredDefinition = '!';
                    }

                    // Split up the command definition to get the short and long option separately
                    $commandDefinition = explode('|', $commandDefinition);

                    // Save the short option but remove the additional hyphen as short options only use 1 hyphen
                    $optionShortcut = ltrim($commandDefinition[0], '-');

                    // Save the short and long option notations as the main command definition but in the correct format
                    $commandDefinition = "$requiredDefinition-$optionShortcut|--$commandDefinition[1]";
                }

                // By default, options are classed as optional at first
                $optionOptionality = 'optional';

                // A ! at the start of a option means it has been defined as required
                if($commandDefinition[0] === '!')
                {
                    // Declare this option as required
                    $optionOptionality = 'required';

                    // Remove the ! from the command definition
                    $commandDefinition = ltrim($commandDefinition, '!');
                }

                // Option defaults are declared as false if they don't have one
                $defaultOptionValue = false;

                // An optional option must have a = sign to set a default value
                if($optionOptionality === 'optional' && strpos($commandDefinition, '=') !== false)
                {
                    // Left of the '=' is the command name, right side of '=' is the default value
                    [$commandDefinition, $defaultOptionValue] = explode('=', $commandDefinition);
                }

                // Required options are not allowed to set default values as they are required
                if($optionOptionality === 'required' && strpos($commandDefinition, '=') !== false)
                {
                    $parsedCommandSignature['error'] = "Option: $commandDefinition defined as required but trying to set a default value";
                    return $parsedCommandSignature;
                }

                /*
                 * Save our option using its declared optionality type.
                 *
                 * We store either required or optional options with default
                 * values.
                 */
                if($optionOptionality === 'required')
                {
                    // Else it means the option has a default value and is optional
                    $parsedCommandSignature['options'][$optionOptionality][] = $commandDefinition;
                }
                else if($optionOptionality === 'optional')
                {
                    // Else it means the option has a default value and is optional
                    $parsedCommandSignature['options'][$optionOptionality][] = [
                        'name' => $commandDefinition,
                        'default' => $defaultOptionValue,
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
            return ['status' => false, 'error' => "The argument: $arg is required but not passed."];
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

            // Error: The option is required but is not present or is empty
            return ['status' => false, 'error' => "The option: $option is required but not given."];
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