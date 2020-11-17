<?php

namespace App\Console;

use Polyel\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected array $commandActions = [

        'welcome' => \App\Console\Commands\WelcomeCommand::class,

    ];
}