<?php

namespace Oro\Cli\Command\Repository;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Sync extends AbstractSync
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('repository:sync')
            ->setDescription(
                'Synchronize the monolithic repository subtrees with upstream repositories.'
            )
            ->addUsage('application/crm')
            ->addUsage('package/platform');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->assertWorkingTreeEmpty();
        $this->processSync($input, $output, $this->getRepositories($input));
    }

    /**
     * {@inheritdoc}
     */
    protected function doSync(InputInterface $input, OutputInterface $output, array $repositories)
    {
        $twoWay = $input->getOption('two-way');

        foreach ($repositories as $codePath => $repository) {
            $alias = $this->getAlias($codePath);
            $output->writeln("Working on \"{$codePath}\" subtree from \"{$repository}\" repository.");
            $this->fetchLatestDataFromRemoteBranch($alias, $repository);
            $this->updateSubtree($codePath, $twoWay);
        }
    }
}
