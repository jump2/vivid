<?php

namespace Vivid\Server;

use Symfony\Component\Console\Application;
use Vivid\Server\Command\ServerCommand;

class Command
{
    public function run()
    {
        $application = new Application('Vivid Framework', '1.0.0');
        $application->addCommands([new ServerCommand()]);
        $application->run();
    }
}