<?php

namespace Polyel\Console;

trait Io
{
    private $defaultStyle = "\e[0m";

    // By default stty availability isn't checked, so we set it to false to start with
    private $sttyAvailable = false;

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

    public function section(string $section)
    {
        // The section top and bottom is surrounded by the following char
        $sectionTopAndBottom = str_repeat('─', 58);

        /*
         * Calculate the left section spacing in order to get the section text to be centre.
         * We set the width to 58 because the section text includes a left and right │ char
         * which makes up the total width of 60.
         */
        $leftSectionSpacing = round(((58 - strlen($section)) / 2), 0, PHP_ROUND_HALF_DOWN);

        // To work out the correct right spacing, find the remaining amount to get to the total of 60 width
        $rightSectionSpacing = 58 - ($leftSectionSpacing + strlen($section));

        $SectionSides = str_repeat(' ', 58);
        $leftSectionSpacing = str_repeat(' ', $leftSectionSpacing);
        $rightSectionSpacing = str_repeat(' ', $rightSectionSpacing);

        $this->writeNewLine('┌' . $sectionTopAndBottom . '┐');
        $this->writeNewLine('│' . $SectionSides . '│');
        $this->writeNewLine('│' . $leftSectionSpacing . "\e[1m" . $section . "\e[0m" . $rightSectionSpacing . '│');
        $this->writeNewLine('│' . $SectionSides . '│');
        $this->writeNewLine('└' . $sectionTopAndBottom . '┘');
    }

    public function title(string $title)
    {
        /*
         * Calculate the left section spacing in order to get the title text to be centre.
         * We set the width to 58 because the title text includes a left and right [ or ] char
         * which makes up the total width of 60.
         */
        $leftTitleSpacing = round(((58 - strlen($title)) / 2), 0, PHP_ROUND_HALF_DOWN);

        // To work out the correct right spacing, find the remaining amount to get to the total of 60 width
        $rightTitleSpacing = 58 - ($leftTitleSpacing + strlen($title));

        $leftTitleSpacing = str_repeat(' ', $leftTitleSpacing);
        $rightTitleSpacing = str_repeat(' ', $rightTitleSpacing);

        $title = $leftTitleSpacing . $title . $rightTitleSpacing;

        $this->writeNewLine('[' . "\e[1m" . $title . "\e[0m" . ']');
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
        fwrite(STDERR, "\e[41;1;33m[Fatal]$this->defaultStyle $fatal\n\n");
        exit(1);
    }

    public function ask($question)
    {
        $answer = readline($question);

        readline_add_history($question);

        return $answer;
    }

    public function askSecret($question)
    {
        // Output the question prompt
        $this->writeLine($question);

        // The default availability status for the stty command is false, so we can check if it is available...
        if($this->sttyAvailable === false)
        {
            // Run a shell command to check if the stty command is available or not
            $sttyAvailable = \Swoole\Coroutine\System::exec('stty 2>&1');

            // If the exit code is 0, it means successful and that we have the stty available to us
            if($sttyAvailable['code'] === 0)
            {
                // So that we don't have to check availability again
                $this->sttyAvailable = true;
            }
            else
            {
                /*
                 * Else the exit code was more than 0, meaning the stty command is not available
                 *
                 * This fatal call will also force an exit return
                 */
                $this->fatal("stty command not available: Unable hide response.");
            }
        }

        /*
         * The stty command allows us to turn off echoing so that
         * the console input for a secret does not show up when
         * the user types their response but, we first get the original
         * terminal mode, so we can later reset the terminal to its
         * original settings before we turned echoing off to hide
         * input.
         */
        $terminalMode = \Swoole\Coroutine\System::exec('stty -g')['output'];
        \Swoole\Coroutine\System::exec('stty -echo');

        // Capture the secret input
        $secret = fgets(STDIN);

        // Re-enable the terminal with its original settings and echoing back on
        \Swoole\Coroutine\System::exec("stty $terminalMode");

        // Output a newline char
        $this->writeNewLine(' ');

        return trim($secret);
    }
}