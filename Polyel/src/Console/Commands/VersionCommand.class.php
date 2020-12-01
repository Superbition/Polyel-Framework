<?php

namespace Polyel\Console\Commands;

use Polyel;
use Polyel\Console\Command;

class VersionCommand extends Command
{
    public string $description = 'Displays the current installed version of Polyel';

    public function execute()
    {
        $polyelVersion = Polyel::version();

        $this->writeLine("\e[35mPolyel Version: ");

        $this->writeNewLine($polyelVersion, 2);
    }
}