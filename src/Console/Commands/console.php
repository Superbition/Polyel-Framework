<?php

use Polyel\Console\Facade\Console;

Console::command('list');

Console::command('help {command : The command you want to display help text for}');

Console::command('version');

Console::command('create:command {command-name : The name of the new command to create}');

Console::command('create:controller {controller-name : The name of the new controller to create}
                                            {--action : Optional name of the action method to create}'
);

Console::command('create:middleware {middleware-name : The name of the new middleware to create}');

Console::command('create:element {element-name : The name of the new view element to create}
                                         {--element-template : Set to create the element view template file as well}');

Console::command('create:service {service-name : The name of the service you want to create}
                                         {--service-only : Only create the service class and not the supplier}                                 
');

Console::command('key:generate');

Console::command('flush:sessions');