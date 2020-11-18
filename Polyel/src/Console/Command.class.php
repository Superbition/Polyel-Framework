<?php

namespace Polyel\Console;

class Command
{
    private array $commandInput;

    public function __construct()
    {

    }

    public function useInput(array $commandInput)
    {
        $this->commandInput = $commandInput;

        return $this;
    }
}