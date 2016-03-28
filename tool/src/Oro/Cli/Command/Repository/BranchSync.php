<?php

namespace Oro\Cli\Command\Repository;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class BranchSync extends AbstractSync
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('repository:branch-sync')
            ->addArgument('branch-name', InputArgument::REQUIRED, 'Branch name')
            ->addOption(
                'generate-branch-name',
                null,
                InputOption::VALUE_NONE,
                'Generate branch name base on next rule "{codePath}-{branch-name}"'
            )
            ->setDescription(
                'Synchronize the specific branch of the monolithic repository subtrees with upstream repositories.'
            )
            ->addUsage('task/TA-123 application/crm')
            ->addUsage('feature/FA-234 package/platform');
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
    protected function getAllRepositories(InputInterface $input)
    {
        $branchName = $input->getArgument('branch-name');

        $branches = $this->getRemoteBranches();

        $filteredRepositories = [];
        foreach ($this->repositories as $codePath => $origin) {
            $branchNameWithPath = 'remotes/' . $this->getAlias($codePath) . '/' . $branchName;
            if (in_array($branchNameWithPath, $branches, true)) {
                $filteredRepositories[$codePath] = $origin;
            }
        }

        if (empty($filteredRepositories)) {
            throw new \RuntimeException(
                "There is no remote repositories that contain the \"{$branchName}\" branch."
            );
        }

        return $filteredRepositories;
    }

    /**
     * @return string[]
     */
    protected function getRemoteBranches()
    {
        $branches = [];
        $this->execCmd('git branch -a | grep remotes/', true, $branches);
        $branches = array_map('trim', $branches);

        return $branches;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSync(InputInterface $input, OutputInterface $output, array $repositories)
    {
        $twoWay = $input->getOption('two-way');
        $branchName = $input->getArgument('branch-name');
        $generateBranchName = $input->getOption('generate-branch-name');

        $previousLocalBranchName = '';
        foreach ($repositories as $codePath => $repository) {
            $alias = $this->getAlias($codePath);
            $output->writeln("Working on \"{$codePath}\" subtree from \"{$repository}\" repository.");
            $this->fetchLatestDataFromRemoteBranch($alias, $repository, $branchName);

            $branchInfo = [];
            $localBranchName = $generateBranchName
                ? $codePath . '-' . $branchName
                : $branchName;

            if ($previousLocalBranchName !== $localBranchName) {
                $this->execCmd("git branch --list {$localBranchName}", false, $branchInfo);
                if ($branchInfo) {
                    $this->execCmd("git checkout {$localBranchName}", true);
                } else {
                    /* We want make branch only from master */
                    $this->execCmd("git checkout master", true);
                    $this->execCmd("git checkout -b {$localBranchName}", true);
                }
                $previousLocalBranchName = $localBranchName;
            }

            $this->updateSubtree($codePath, $twoWay, $branchName);
        }
    }
}
