<?php

namespace Oro\Cli\Command\Repository;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use Oro\Cli\Command\RootCommand;

class BranchSync extends Sync
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('repository:branch-sync')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to subtree folder')
            ->addArgument(
                'branch-name',
                InputArgument::REQUIRED,
                'Get the specific branch ( Note: If branch with name \'{codePath}-{branch-name}\' doesn\'t exits, it will create new one )'
            )
            ->addOption(
                'two-way',
                null,
                InputOption::VALUE_NONE,
                'Whether the synchronization of upstream repositories is needed'
            )
            ->setDescription('Synchronize repository subtrees with specific branch in upstream application or package repositories.')
            ->addUsage('application/crm task/TA-123')
            ->addUsage('package/platform feature/FA-234');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');
        $twoWay = $input->getOption('two-way');
        $branchName = $input->getArgument('branch-name');

        if (!isset($this->repositories[$path])) {
            $output->writeln("There are no repository registered for \"{$path}\" path.");

            return;
        }

        $untrackedFiles = [];
        $this->execCmd('git status --porcelain', true, $untrackedFiles);
        if (count($untrackedFiles)) {
            $output->writeln("There are untracked files in working tree, to continue please clean the working tree.");

            return;
        }

        $repository = $this->repositories[$path];
        /* Changing directory as subtree commands must be executed from the repository root */
        $currentDir = getcwd();
        chdir($this->getRootDir());
        try {
            $this->doBranchSync($output, $path, $repository, $branchName, $twoWay);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
        /* Restore original directory */
        chdir($currentDir);
    }

    /**
     * @param OutputInterface $output
     * @param string          $path
     * @param string          $repository
     * @param string          $branchName
     * @param bool            $twoWay
     */
    protected function doBranchSync(OutputInterface $output, $path, $repository, $branchName, $twoWay)
    {
        $remotes = [];
        $this->execCmd('git remote', true, $remotes);
        $alias = $path.'_upstream';
        $output->writeln("Working on {$path} subtree from {$repository} repository.");
        if (in_array($alias, $remotes, true)) {
            /* Fetch master from remote */
            $this->execCmd("git fetch {$alias} master");
        } else {
            /* Add remote repository if it was not added yet */
            $this->execCmd("git remote add -f {$alias} {$repository}");
        }

        $branchInfo = [];
        $this->execCmd("git branch --list {$path}_{$branchName}", false, $branchInfo);
        if ($branchInfo) {
            $this->execCmd("git checkout {$path}_{$branchName}", true);
        } else {
            $this->execCmd("git checkout -b {$path}_{$branchName}", true);
        }

        /* Add subtree prefix for remote master */
        $this->execCmd("git subtree add --prefix={$path} {$alias} $branchName", false);

        /* Pull all updates from remote master */
        if ($this->execCmd("git subtree pull --prefix={$path} {$alias} $branchName") && $twoWay) {
            /* Push all subtree changes to remote upstream repository */
            $this->execCmd("git subtree push --prefix={$path} {$alias} $branchName");
        }
    }

}
