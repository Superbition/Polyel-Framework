<?php

namespace Polyel\Console\Commands;

use Polyel\Console\Command;

class CreateServiceCommand extends Command
{
    public string $description = 'Generates a new service and supplier class';

    public function execute()
    {
        $serviceName = $this->argument('service-name');
        $serviceOnlyOption = $this->option('--service-only');

        $this->writeNewLine('Building Service stub source and destination file paths');
        $sourceStub = APP_DIR . "/$this->vendorStubPath/Service.stub";
        $distService = APP_DIR . "/app/Services/$serviceName.php";

        $this->writeNewLine('Generating a new Service...');
        copy($sourceStub, $distService);

        $this->writeNewLine('Replacing Service placeholders', 2);
        $newServiceClass = str_replace('{{ ServiceClassName }}', trim($serviceName), file_get_contents($distService));

        $this->writeNewLine('Saving new service file...');
        file_put_contents($distService, $newServiceClass);

        if($serviceOnlyOption === false)
        {
            $this->writeNewLine('Building Service Supplier stub source and destination file paths');
            $sourceStub = APP_DIR . "/$this->vendorStubPath/ServiceSupplier.stub";
            $distService = APP_DIR . "/app/Services/Suppliers/$serviceName.php";

            $this->writeNewLine('Generating a new Service Supplier...');
            copy($sourceStub, $distService);

            $this->writeNewLine('Replacing Service Supplier placeholders', 2);
            $newServiceClass = str_replace('{{ ServiceSupplierClassName }}',
                trim($serviceName . 'Supplier'),
                file_get_contents($distService)
            );

            $this->writeNewLine('Saving new service supplier file...');
            file_put_contents($distService, $newServiceClass);
        }
        else
        {
            $this->info('Not generating matching service supplier class');
        }

        $this->writeNewLine("\e[32mCreated a new Service called: $serviceName", 2);
    }
}