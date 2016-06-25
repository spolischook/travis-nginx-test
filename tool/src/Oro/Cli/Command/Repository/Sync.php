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
        parent::configure();

        $this
            ->setName('repository:sync')
            ->setDescription('Synchronize the monolithic repository subtrees with upstream repositories.');
    }

    /**
     * {@inheritdoc}
     */
    protected function doSync(InputInterface $input, OutputInterface $output)
    {
        $branchName = $this->getBranch();

        foreach ($this->getApplicableRepositories() as $codePath => $repository) {
            $this->updateSubtree($repository, $codePath, $this->getBranch());
        }

        $this->updateRemote($branchName, $branchName);

        if ($this->isTwoWay()) {
            foreach ($this->getApplicableRepositories() as $prefix => $repository) {
                $remoteBranch = $this->resolveRemoteBranch($branchName, $prefix);
                $remoteAlias = $this->getRemoteAlias($prefix);
                $subtreeBranch = $this->getSubtreeBranch($prefix);

                $this->updateRemote($subtreeBranch, $remoteBranch, $remoteAlias);
            }
        }
    }
}
