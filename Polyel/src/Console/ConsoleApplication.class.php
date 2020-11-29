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
    use CommandDefinitionRules;

    private array $commands = [];

    private array $signatures = [];

    private array $optionsThatAcceptArrays = [];

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
                        fwrite(STDERR, 'Exit Exception: ' . $exception->getStatus() . "\n\n");

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

    public function includeReservedOptions($signature)
    {
        // Add core native/reserved options to the signature
        return $signature . '{--h|help=false : Shows this help message} {--q|quiet=false : Turn off all console output except fatal errors} {--v|verbosity=false : Increase the output of debug messages e.g. -v or -vvv...}';
    }

    public function parseCommandSignature($commandSignature)
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

            'descriptions' => [],
        ];

        [$parsedCommandSignature['descriptions'], $commandDefinitions] = $this->processInputDescriptions($commandDefinitions);

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

                // Check the command definition to keep track of options that accept arrays
                $this->processOptionsThatAcceptArrays($commandDefinition);

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

                // Required arguments are not allowed to set default values as they are required
                if($argumentOptionality === 'required' && strpos($commandDefinition, '=') !== false)
                {
                    // However, the argument is allowed to set the input array wildcard
                    if(strpos($commandDefinition, '=*') === false)
                    {
                        $parsedCommandSignature['error'] = "Argument: $commandDefinition defined as required but trying to set a default value";
                        return $parsedCommandSignature;
                    }
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

    private function processInputDescriptions(array $commandDefinitions)
    {
        $descriptions = [];

        foreach($commandDefinitions as &$definition)
        {
            // If we find a ':' it means we have a input description
            if(strpos($definition, ':') !== false)
            {
                [$definition, $inputDesc] = explode(':', $definition);

                // Remove left over whitespace
                $definition = trim($definition);

                // Check if the definition contains a default value that is between " "
                if(strpos($definition, '"') !== false && strpos($definition, '=') !== false)
                {
                    // Getting the default value between quotes helps maintain intended whitespace
                    $inputDefaultValue = $this->getStringsBetween($definition, '"', '"', false)[0];

                    $definition = explode('=', $definition)[0];
                    $definition .= '=' . $inputDefaultValue;
                }

                $inputName = trim($definition);

                // Remove any optional or required markers
                $inputName = ltrim($inputName, '?');
                $inputName = ltrim($inputName, '!');

                // Extract the input name and leave the default value
                $inputName = explode('=', $inputName)[0];

                // Convert the input name into an array based on if a short and long option is used
                $inputName = explode('|', $inputName);

                // A count of more than 1 means we have a short and long option
                if(count($inputName) > 1)
                {
                    // Convert each notation to use the correct number of hyphen e.g. -a --all
                    $inputName[0] = '-' . ltrim($inputName[0], '-');
                    $inputName[1] = "--$inputName[1]";
                }

                $inputDesc = trim($inputDesc);

                // For each input notation if they were found, set its name with a description
                foreach($inputName as $name)
                {
                    $descriptions[$name] = $inputDesc;
                }
            }
        }

        // Break the call by ref link
        unset($definition);

        return [$descriptions, $commandDefinitions];
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
            // We have found a argument expecting array index if the * wildcard is found...
            if(strpos($arg['name'], '=*') !== false)
            {
                /*
                 * Save the original index name for the argument that
                 * expects array input, as we need to keep saving
                 * values to it.
                 */
                $argumentArrayIndex = rtrim($arg['name'], '=*');

                /*
                 * Remove all of the used arguments up until
                 * the point of our array input index. If we
                 * don't remove the previous array elements
                 * we would start collecting values for our
                 * argument array from the wrong index.
                 */
                array_splice($inputArguments, 0, $key);

                // Loop through and collect argument values for the argument that expects an array
                foreach($inputArguments as $inputKey => $inputValue)
                {
                    /*
                     * The only way to stop collecting values is if we
                     * encounter a argument separator or if we reach the
                     * end of the arguments.
                     */
                    if($this->isArgumentSeparator($inputValue))
                    {
                        unset($inputArguments[$inputKey]);

                        break;
                    }

                    // Save an argument as part of the array...
                    $processedInputArguments[$argumentArrayIndex][] = $inputValue;

                    // Remove the saved argument value, we don't want it showing up in other arguments
                    unset($inputArguments[$inputKey]);
                }

                /*
                 * Reset the input arguments array to re-sync it back up with
                 * the number of parsed arguments from the command definition.
                 * The array_combine re-syncs the input arguments up with the
                 * foreach loop with the command definition arguments again.
                 * If there are any arguments after argument array values, then
                 * the argument data will match up with the number of arguments
                 * from the definition. The range starts the index from the
                 * correct position with the amount of left over input arguments,
                 * if any are left.
                 */
                if(!empty($inputArguments))
                {
                    // Key is the index of the current argument from the command definition
                    $inputArgumentStartIndex = $key;

                    $inputArgumentCount = count($inputArguments);

                    // We can only increase the start index if there is another argument to process
                    if($inputArgumentCount >= 1)
                    {
                        $inputArgumentStartIndex++;

                        // Re-balance the argument end index if the start is more
                        if($inputArgumentStartIndex > $inputArgumentCount)
                        {
                            $inputArgumentCount++;
                        }
                    }

                    // Re-sync the input arguments with the command definition key index
                    $inputArguments = array_combine(range($inputArgumentStartIndex, $inputArgumentCount), $inputArguments);
                }

                continue;
            }

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
            return ['status' => false, 'error' => "The argument: $arg[name] is required but not passed."];
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

                // Check if the option is defined to accept arrays, if an array is set
                if($error = $this->doesOptionExpectAnArray($options['values'], $options['notations']))
                {
                    return ['status' => false, 'error' => $error];
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

                // Check if the option is defined to accept arrays, if an array is set
                if(isset($options['values']) && $error = $this->doesOptionExpectAnArray($options['values'], $options['notations']))
                {
                    return ['status' => false, 'error' => $error];
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

    public function getCommandSignatureFor($command)
    {
        if(array_key_exists($command, $this->signatures))
        {
            return $this->signatures[$command];
        }

        return false;
    }
}