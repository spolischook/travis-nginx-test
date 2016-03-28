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
            ->addArgument('branch', InputArgument::REQUIRED, 'Branch name')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show a list of repositories that have the specified branch'
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
        $dryRun = $input->getOption('dry-run');
        if ($dryRun) {
            $repositories = $this->getRepositories($input);
            foreach ($repositories as $repository) {
                $output->writeln($repository);
            }
        } else {
            $this->assertWorkingTreeEmpty();
            $repositories = $this->getRepositories($input);
            $this->processSync($input, $output, $repositories);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getAllRepositories(InputInterface $input)
    {
        $branchName = $input->getArgument('branch');

        $remoteBranches = $this->getRemoteBranches();

        $filteredRepositories = [];
        foreach ($this->repositories as $codePath => $origin) {
            $branchNameWithPath = 'remotes/' . $this->getAlias($codePath) . '/' . $branchName;
            if (in_array($branchNameWithPath, $remoteBranches, true)) {
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
        $branchName = $input->getArgument('branch');

        if ($this->isLocalBranchExist($branchName)) {
            $this->execCmd("git checkout {$branchName}", true);
        } else {
            /* We want make branch only from master */
            $this->execCmd("git checkout master", true);
            $this->execCmd("git checkout -b {$branchName}", true);
        }

        foreach ($repositories as $codePath => $repository) {
            $output->writeln("Working on \"{$codePath}\" subtree from \"{$repository}\" repository.");
            $this->fetchLatestDataFromRemoteBranch($this->getAlias($codePath), $repository, $branchName);
            $this->updateSubtree($codePath, $twoWay, $branchName);
        }
    }

    /**
     * @param string $branchName
     *
     * @return bool
     */
    protected function isLocalBranchExist($branchName)
    {
        $existingBranches = [];
        $this->execCmd("git branch --list {$branchName}", false, $existingBranches);

        return !empty($existingBranches);
    }
}
