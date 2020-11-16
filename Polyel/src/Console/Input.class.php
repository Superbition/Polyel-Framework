<?php

namespace Polyel\Console;

class Input
{
    public string $command = '';

    public array $arguments = [];

    public array $options = [];

    private array $optionCount = [];

    public function __construct(array $argv, int $argc)
    {
        // Only process arguments if they exist after the script name
        if($argc > 1)
        {
            /*
             * Remove the script name and first argument in
             * order to check if the name of the command to run has
             * been given.
             */
            $command = array_splice($argv, 0, 2)[1];

            // Only assign a command if it is not an option as it is possible that no command is given, only options
            if($this->isNotAnOption($command))
            {
                $this->command = $command;
            }
            else
            {
                /*
                 * Else the first argument is not a command
                 * but an option, so we shift it back onto the
                 * argv array to be parsed. At this stage it means
                 * only options and or sub arguments were given.
                 */
                array_unshift($argv, $command);
            }

            // Send the rest of argv to be parsed and processed into command segments
            $this->parseCommandInput($argv);
        }

        //var_dump($argv);
    }

    private function parseCommandInput(array $argv)
    {
        // The different segments to split the command input into
        $parsedCommandSegments = [
            'arguments' => [],
            'options' => [],
        ];

        // Used to detect when an option is waiting for a value during a loop cycle
        $optionIsWaitingForValue = false;

        // Used to keep track of the previous argument in the loop
        $lastArgument = null;

        foreach($argv as $arg)
        {
            /*
             * Detect when an option is waiting for its value as the
             * flag $optionIsWaitingForValue will be set to true and the
             * current argument won't be an option. Or we will have
             * encountered an argument separator which will be '--'.
             */
            if(($optionIsWaitingForValue && $this->isNotAnOption($arg)) || $this->isArgumentSeparator($arg))
            {
                // Only assign the value to the option if the current argument is not an argument separator
                if($this->isNotArgumentSeparator($arg))
                {
                    /*
                     * We use the last argument name because at this stage we already have the
                     * last arguments value but we need the previous argument name in order
                     * to set a new option and its value.
                     */
                    $parsedCommandSegments = $this->setANewOption($lastArgument, $arg, $parsedCommandSegments);
                }

                $optionIsWaitingForValue = false;

                /*
                 * We have collected the option's value or found an argument
                 * separator, we can now move onto the next argument in the array
                 */
                continue;
            }

            // Always keep track of what the previous argument is
            $lastArgument = $arg;

            // Matches options that start with a - or --
            if($this->isAnOption($arg))
            {
                // Supports the use of -bar=value or --bar=value
                if(strpos($arg, '=') !== false)
                {
                    $arg = explode('=', $arg);

                    $parsedCommandSegments = $this->setANewOption($arg[0], $arg[1], $parsedCommandSegments);

                    continue;
                }

                // Supports the use of -bValue or -fValue etc.
                if(!strpos($arg, '=') !== false && strlen($arg) > 2 && $this->isAShortOption($arg))
                {
                    $option[0] = substr($arg, 0, 2);
                    $option[1] = substr($arg, 2, strlen($arg));

                    $parsedCommandSegments = $this->setANewOption($option[0], $option[1], $parsedCommandSegments);

                    continue;
                }

                /*
                 * If the option already exists and is waiting for a value
                 * as the next argument, it means we should not set the
                 * default 'true' value because the option has been used
                 * more than once, indicting that it should now become an
                 * array of values.
                 *
                 * This supports the usage of --domain example.com --domain example.co.uk etc.
                 */
                if(!isset($parsedCommandSegments['options'][$arg]))
                {
                    /*
                     * If we get to this stage, it means we have a option
                     * using a space to indicate that the next argument in
                     * the array is its value e.g. --bar foo or -bar "foo bar" etc.
                     * So as a default value, we set the option to 'true' as it
                     * is already present, this is also the default value for
                     * an option.
                     */
                    $parsedCommandSegments['options'][$arg] = true;
                }

                /*
                 * Set the flag that an option is waiting for its value
                 * so it can get picked up during the next loop cycle.
                 */
                $optionIsWaitingForValue = true;
                continue;
            }

            /*
             * At this stage we have a normal positional argument and not an option.
             * So we can store the argument and continue onto the next.
             */
            $parsedCommandSegments['arguments'][] = $arg;
            continue;
        }

        var_dump($parsedCommandSegments);
    }

    private function isAnOption($arg)
    {
        // Supports short and long options: -b or --bar etc.
        return $this->isAShortOption($arg) || $this->isALongOption($arg);
    }

    private function isNotAnOption($arg)
    {
        return !$this->isAnOption($arg);
    }

    private function isAShortOption($arg)
    {
        // Make sure the argument doesn't start with two hyphens but does start with one hyphen
        return strpos($arg, '--') !== 0 && strpos($arg, '-') === 0;
    }

    private function isALongOption($arg)
    {
        // Make sure the argument starts with two hyphens
        return strpos($arg, '--') === 0;
    }

    private function isArgumentSeparator($arg)
    {
        return $arg === '--';
    }

    private function isNotArgumentSeparator($arg)
    {
        return !$this->isArgumentSeparator($arg);
    }

    private function setANewOption($optionName, $optionValue, $parsedCommandSegments)
    {
        // Create the option count for the option if it doesn't already exist
        if(!isset($this->optionCount[$optionName]))
        {
            $this->optionCount[$optionName] = 0;
        }

        // Increasing the option count means we are tracking how many times an option is used
        $this->optionCount[$optionName]++;

        /*
         * If the option count is more than 1, it means we have an
         * option that has been defined more than once, so we shall turn that
         * option and its values into an array, saving any previous defined values.
         *
         * This supports the usage of --domain example.com --domain example.co.uk etc.
         */
        if($this->optionCount[$optionName] > 1 && isset($parsedCommandSegments['options'][$optionName]))
        {
            // Create the array if it hasn't already been done...
            if(!is_array($parsedCommandSegments['options'][$optionName]))
            {
                // Get the current value from the option and convert it into an array so it doesn't get overwritten.
                $currentOptionValue = $parsedCommandSegments['options'][$optionName];
                $parsedCommandSegments['options'][$optionName] = [$currentOptionValue];
            }

            // Append new option values onto the array...
            $parsedCommandSegments['options'][$optionName][] = $optionValue;
        }
        else
        {
            // Create a new option and assign its value
            $parsedCommandSegments['options'][$optionName] = $optionValue;
        }

        return $parsedCommandSegments;
    }
}