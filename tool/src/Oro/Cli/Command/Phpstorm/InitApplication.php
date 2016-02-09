<?php

namespace Oro\Cli\Command\Phpstorm;

use Oro\Cli\Command\RootCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Command copy PHPStorm fonfiguration from config folder to root .idea folder so proper application settings
 * could be activated.
 *
 * Add or update application config files if any PHPStorm configuration changes should be done.
 */
class InitApplication extends RootCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('phpstorm:init-application')
            ->addArgument('application', InputArgument::REQUIRED, 'Application name')
            ->setDescription('Switch PHPStorm settings and optimize developer experience for requested application.');
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application  = $input->getArgument('application');

        $applicationDir = __DIR__ . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR . $application;

        if (is_dir($applicationDir)) {
            $configDir = $this->getRootDir() . DIRECTORY_SEPARATOR . '.idea';

            $files = glob($applicationDir . DIRECTORY_SEPARATOR . "*.*");
            foreach ($files as $file) {
                if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $output->writeln("Copying {$file} to {$configDir}");
                }
                copy($file, str_replace($applicationDir, $configDir, $file));
            }

            $output->writeln("Configuration updated. Please restart PHPStorm.");
        } else {
            $output->writeln("Configuration for application \"{$application}\" doesn't exist");
        }
    }
}