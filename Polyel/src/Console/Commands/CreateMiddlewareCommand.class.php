<?php

namespace Polyel\Console\Commands;

use Polyel\Console\Command;

class CreateMiddlewareCommand extends Command
{
    public string $description = 'Generates a new Middleware class';

    public function execute()
    {
        $middlewareName = $this->argument('middleware-name');

        $this->writeNewLine('Building Middleware stub source and destination file paths');
        $sourceStub = ROOT_DIR . '/Polyel/src/Console/stubs/Middleware.stub';
        $distMiddleware = ROOT_DIR . "/app/Http/Middleware/$middlewareName.php";

        $this->writeNewLine('Generating a new Middleware class...');
        copy($sourceStub, $distMiddleware);

        $this->writeNewLine('Replacing Middleware placeholders', 2);
        $newCommandClass = str_replace('{{ MiddlewareClassName }}', trim($middlewareName), file_get_contents($distMiddleware));
        file_put_contents($distMiddleware, $newCommandClass);

        $this->writeNewLine("\e[32mCreated a new Middleware called: $middlewareName", 2);
    }
}