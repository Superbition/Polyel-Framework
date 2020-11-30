<?php

namespace Polyel\Console\Commands;

use Polyel\Console\Command;

class CreateControllerCommand extends Command
{
    public string $description = 'Generates a new controller class';

    public function execute()
    {
        $controllerName = $this->argument('controller-name');
        $controllerActionName = $this->option('--action');

        $this->writeNewLine('Building Controller stub source and destination file paths');
        $sourceStub = ROOT_DIR . '/Polyel/src/Console/stubs/Controller.stub';
        $distController = ROOT_DIR . "/app/Http/Controllers/$controllerName.php";

        $this->writeNewLine('Generating a new Controller...');
        copy($sourceStub, $distController);

        $this->writeNewLine('Replacing Controller placeholders', 2);
        $newCommandClass = str_replace('{{ ControllerClassName }}', trim($controllerName), file_get_contents($distController));

        if($controllerActionName !== false)
        {
            $action = "\n\n\tpublic function $controllerActionName()
    {
        
    }";

            $newCommandClass = str_replace('{{ ControllerAction }}', $action, $newCommandClass);
        }
        else
        {
            $newCommandClass = str_replace('{{ ControllerAction }}', '', $newCommandClass);
        }

        file_put_contents($distController, $newCommandClass);

        $this->writeNewLine("\e[32mCreated a new Controller called: $controllerName", 2);
    }
}