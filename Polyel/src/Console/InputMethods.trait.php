<?php

namespace Polyel\Console;

trait InputMethods
{
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
}