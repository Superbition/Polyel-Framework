<?php

namespace Polyel\Console\Commands;

use Polyel\Console\Command;

class CreateCommandCommand extends Command
{
    public string $description = 'Generates a new console command class';

    public function execute()
    {
        $commandName = $this->argument('command-name');

        $this->writeNewLine('Building Command stub source and destination file paths');
        $sourceStub = APP_DIR . "/$this->vendorStubPath/Command.stub";
        $distCommand = APP_DIR . "/app/Console/Commands/$commandName.php";

        $this->writeNewLine('Generating a new Command...');
        copy($sourceStub, $distCommand);

        $this->writeNewLine('Replacing Command placeholders', 2);
        $newCommandClass = str_replace('{{ CommandClassName }}', trim($commandName), file_get_contents($distCommand));
        file_put_contents($distCommand, $newCommandClass);

        $this->writeNewLine("\e[32mCreated a new Command called: $commandName", 2);
    }
}