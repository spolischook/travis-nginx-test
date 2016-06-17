<?php

namespace Oro\Cli\Command\Repository;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BranchSync extends Sync
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('repository:branch-sync')
            ->addOption(
                'add-subtree',
                'add',
                InputOption::VALUE_NONE,
                'Add subtree to new branch. In case we need to sync 1.7 to master'
            )
            ->setDescription(
                'Synchronize the specific maintenance branch of the monolithic repository subtrees with ' .
                'upstream repositories. Add subtree to new branch'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function doSync(InputInterface $input, OutputInterface $output)
    {
        $addSubtree = (bool)$input->getOption('add-subtree');

        if ($addSubtree) {
            foreach ($this->getApplicableRepositories() as $codePath => $repository) {
                $remoteBranch = $this->resolveRemoteBranch($this->getBranch(), $codePath);
                $remoteAlias = $this->getRemoteAlias($codePath);

                $this->execCmd(
                    "git subtree add --prefix={$codePath} {$remoteAlias} {$remoteBranch}",
                    false
                );
            }
        }

        parent::doSync($input, $output);
    }
}
