<?php

namespace Oro\Cli\Command\Repository;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class Sync extends AbstractSync
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('repository:sync')
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to subtree folder')
            ->addOption(
                'two-way',
                null,
                InputOption::VALUE_NONE,
                'Whether the synchronization of upstream repositories is needed'
            )
            ->setDescription('Synchronize repository subtrees with upstream application and package repositories.')
            ->addUsage('application/crm')
            ->addUsage('package/platform');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');
        $twoWay = $input->getOption('two-way');

        try {
            $this->validateWorkingTree();
            $repositories = $this->getRepositoriesFromThePath($path);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());

            return;
        }

        $this->processSync($output, $repositories, $twoWay);
    }

    /**
     * {@inheritdoc}
     */
    protected function doSync(OutputInterface $output, array $repositories, $twoWay)
    {
        foreach ($repositories as $codePath => $repository) {
            $alias = $this->getAlias($codePath);
            $output->writeln("Working on {$codePath} subtree from {$repository} repository.");
            $this->fetchLatestDataFromRemoteBranch($alias, $repository);
            $this->updateSubtree($codePath, $twoWay);
        }
    }
}
