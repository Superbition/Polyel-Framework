<?php

namespace Polyel\Console;

class Command
{
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
}