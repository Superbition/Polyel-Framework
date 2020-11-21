<?php

namespace Polyel\Console;

class Command
{
    use Io;

    private array $arguments;

    private array $options;

    public function __construct()
    {

    }

    public function useInput(array $arguments, array $options)
    {
        $this->arguments = $arguments;
        $this->options = $options;

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