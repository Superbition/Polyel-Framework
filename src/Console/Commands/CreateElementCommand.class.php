<?php

namespace Polyel\Console\Commands;

use Polyel\Console\Command;

class CreateElementCommand extends Command
{
    public string $description = 'Generates a new view element logic class';

    public function execute()
    {
        $elementName = $this->argument('element-name');
        $elementTemplate = $this->option('--element-template');

        $this->writeNewLine('Building View Element stub source and destination file paths');
        $sourceStub = ROOT_DIR . '/Polyel/src/Console/stubs/Element.stub';
        $distElement = ROOT_DIR . "/app/View/Elements/$elementName.php";

        $this->writeNewLine('Generating a new Element class...');
        copy($sourceStub, $distElement);

        $this->writeNewLine('Replacing Element placeholders');
        $newElementClass = str_replace('{{ ElementClassName }}', trim($elementName), file_get_contents($distElement));

        if($elementTemplate !== false)
        {
            $this->info('Template element name given, creating template file as well');
            file_put_contents(ROOT_DIR . "/resources/elements/$elementTemplate.html", '');

            $newElementClass = str_replace('{{ ElementTemplate }}', "'$elementTemplate'", $newElementClass);
        }
        else
        {
            $this->info('No element template file name given, only creating element class');

            $newElementClass = str_replace('{{ ElementTemplate }}', "''", $newElementClass);
        }

        file_put_contents($distElement, $newElementClass);

        $this->writeNewLine('');

        $this->writeNewLine("\e[32mCreated a new View Element called: $elementName", 2);
    }
}