<?php

namespace Oro\Cli\Command;

use Symfony\Component\Console\Command\Command;

/**
 * Base class for commands that need information folders structure
 */
class RootCommand extends Command
{
    protected function getRootDir()
    {
        return realpath(__DIR__ . '/../../../../../');
    }
}
