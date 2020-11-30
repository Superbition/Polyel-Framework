<?php

namespace Polyel\Console\Commands;

use Polyel\Console\Command;

class CreateElementCommand extends Command
{
    public string $description = 'Generates a new view element logic class';

    public function execute()
    {
        $elementName = $this->argument('element-name');

        $this->writeNewLine('Building View Element stub source and destination file paths');
        $sourceStub = ROOT_DIR . '/Polyel/src/Console/stubs/Element.stub';
        $distElement = ROOT_DIR . "/app/View/Elements/$elementName.php";

        $this->writeNewLine('Generating a new Element class...');
        copy($sourceStub, $distElement);

        $this->writeNewLine('Replacing Element placeholders', 2);
        $newElementClass = str_replace('{{ ElementClassName }}', trim($elementName), file_get_contents($distElement));
        file_put_contents($distElement, $newElementClass);

        $this->writeNewLine("\e[32mCreated a new View Element called: $elementName", 2);
    }
}