<?php

namespace Oro\Cli;

use Symfony\Component\Console\Application;

/**
 * Cli application class. Update getCommands method when new command should be added
 */
class OroCliApplication extends Application
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct("Oro Development Repository CLI");
        $this->addCommands($this->getCommands());
    }

    /**
     * Get list of available CLI commands. Do not forget to register your command here when you add a new one.
     *
     * @return array
     */
    public function getCommands()
    {
        $commands = [];
        $commands[] = new Command\Repository\Sync();
        $commands[] = new Command\Repository\BranchSync();
        $commands[] = new Command\Phpstorm\InitApplication();

        return $commands;
    }
}
