<?php

namespace Polyel\Console;

class Command
{
    use Io;

    private array $arguments;

    private array $options;

    private int $verbosityLevel = 0;

    public function __construct()
    {

    }

    public function useInput(array $arguments, array $options)
    {
        $this->arguments = $arguments;
        $this->options = $options;

        return $this;
    }

    public function setVerbosity($verbosityLevel, $quietOption)
    {
        // Set verbosity to -1 because the quiet option is present
        if($quietOption === true)
        {
            $verbosityLevel = -1;
        }

        // If verbosity is set to true, convert it to level 1
        if($verbosityLevel === true)
        {
            $verbosityLevel = 1;
        }
        else if($verbosityLevel === false || $verbosityLevel === 'false')
        {
            $verbosityLevel = 0;
        }

        $this->verbosityLevel = $verbosityLevel;

        return $this;
    }

    protected function argument($argumentName)
    {
        return $this->arguments[$argumentName] ?? null;
    }

    protected function arguments()
    {
        return $this->arguments;
    }

    protected function option($optionName)
    {
        return $this->options[$optionName] ?? null;
    }

    protected function options()
    {
        return $this->options;
    }
}