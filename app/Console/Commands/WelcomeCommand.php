<?php

namespace App\Console\Commands;

use Polyel\Console\Command;

class WelcomeCommand extends Command
{
    public string $description = 'Shows the Polyel welcome screen.';

    public function execute()
    {
        if($user = $this->argument('user'))
        {
            $welcomeMessage = "Hello $user, welcome to the Polyel console!";
        }
        else
        {
            $welcomeMessage = 'Hello, welcome to the Polyel console!';
        }

        $this->writeNewLine('
                          _____      _            _  
                         |  __ \    | |          | | 
                         | |__) |__ | |_   _  ___| |
                         |  ___/ _ \| | | | |/ _ \ |
                         | |  | (_) | | |_| |  __/ |
                         |_|   \___/|_|\__, |\___|_|
                                        __/ |                        
                                       |___/', 2);

        $this->writeNewLine($welcomeMessage, 2);

        $this->writeNewLine('┌─                                                                             ─┐', 4);

        $this->writeNewLine('   Home                                        Documentation');
        $this->writeNewLine('    https://polyel.io/                          https://polyel.io/docs/', 4);

        $this->writeNewLine('   Github                                      Twitter');
        $this->writeNewLine('    https://github.com/Superbition/Polyel       https://twitter.com/PolyelPHP', 4);

        $this->writeNewLine('   Community');
        $this->writeNewLine('    https://phpnexus.io/', 4);

        $this->writeNewLine('└─                                                                             ─┘', 2);
    }
}