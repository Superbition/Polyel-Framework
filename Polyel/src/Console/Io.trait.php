<?php

namespace Polyel\Console;

trait Io
{
    private $defaultStyle = "\e[0m";

    public function writeLine(string $output)
    {
        if($this->verbosityLevel !== -1)
        {
            $output .= $this->defaultStyle;

            fwrite(STDOUT, $output);
        }
    }

    public function writeNewLine(string $output, int $newLines = 1)
    {
        if($this->verbosityLevel !== -1)
        {
            $output .= $this->defaultStyle;

            $output .= str_repeat("\n", $newLines);

            fwrite(STDOUT, $output);
        }
    }

    public function info(string $info)
    {
        $this->writeNewLine("\e[1;30m[Info] $info");
    }

    public function notice(string $notice)
    {
        $this->writeNewLine("\e[0;37;44m[Notice]$this->defaultStyle $notice");
    }

    public function debug(string $debug, int $verbosity = 1)
    {
        if($this->verbosityLevel > 0 && $this->verbosityLevel >= $verbosity)
        {
            $this->writeNewLine("\e[0;30;42m[Debug]$this->defaultStyle $debug");
        }
    }

    public function warning(string $warning)
    {
        $this->writeNewLine("\e[0;30;43m[Warning]$this->defaultStyle $warning");
    }

    public function error(string $error)
    {
        if($this->verbosityLevel !== -1)
        {
            fwrite(STDERR, "\e[1;37;41m[Error]$this->defaultStyle $error\n");
        }
    }

    public function fatal(string $fatal)
    {
        fwrite(STDERR, "\e[41;1;33m[Fatal]$this->defaultStyle $fatal\n");
        exit('Exiting early because of fatal error!');
    }
}