<?php

namespace Polyel\Console;

trait CommandDefinitionRules
{
    private function processOptionsThatAcceptArrays(&$commandDefinition)
    {
        // The '=*' wildcard is used to define an option that accepts an array as input
        if(strpos($commandDefinition, '=*') !== false)
        {
            // We don't want the '*' to be used as a default value, so remove it
            $commandDefinition = rtrim($commandDefinition, '=*');

            // Explode so that any option notations are set individually
            $optionsThatAcceptArrays = explode('|', $commandDefinition);

            /*
             * Keep track of each option that accepts arrays as input
             * so that we check validate that this has been respected
             * later on when processing command option input.
             */
            foreach($optionsThatAcceptArrays as $option)
            {
                $this->optionsThatAcceptArrays[] = $option;
            }
        }
    }

    private function doesOptionExpectAnArray($optionValues, $optionNames)
    {
        /*
         * If we have an option with an array as its input, make
         * sure that it is listed as being allowed to accept arrays
         * as input. An option must be defined using '=*' to accept
         * arrays.
         */
        if(is_array($optionValues) && !in_array($optionNames[0], $this->optionsThatAcceptArrays, true))
        {
            $option = $optionNames[0];

            // Support multiple option name notations: short & long
            if(count($optionNames) > 1)
            {
                $option .= ' or ' . $optionNames[1];
            }

            return "The option: $option is given an array but is not defined to accept arrays.";
        }

        // No array used or option is allowed to have an array
        return false;
    }
}