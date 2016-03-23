<?php

namespace Oro\Cli\Command\Repository;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class BranchSync extends AbstractSync
{
    /**
     * @var string
     */
    protected $branchName;

    /**
     * @var bool
     */
    protected $useCurrentBranchNameAsLocal;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('repository:branch-sync')
            ->addArgument('branch-name', InputArgument::REQUIRED, 'Branch name that you want use')
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to subtree folder')
            ->addOption(
                'two-way',
                null,
                InputOption::VALUE_NONE,
                'Whether the synchronization of upstream repositories is needed'
            )
            ->addOption(
                'generate-branch-name',
                null,
                InputOption::VALUE_NONE,
                'Generate branch name base on next rule \'{codePath}-{branch-name}\''
            )
            ->setDescription('Synchronize repository subtrees with specific branch in upstream application or package repositories.')
            ->addUsage('application/crm task/TA-123')
            ->addUsage('package/platform feature/FA-234');
    }

    protected function getRepositoriesIfPathIsNull()
    {
        $branches = [];
        $this->execCmd('git branch -a  | grep remotes/', true, $branches);
        $filteredCodePathArray = [];
        foreach ($this->repositories as $codePath => $origin) {
            $branchNameWithPath = 'remotes/'.$this->getAlias($codePath).$this->branchName;
            if (in_array($branchNameWithPath, $branches)) {
                $filteredRepositories[$codePath] = $origin;
            }
        }

        if (!count($filteredCodePathArray)) {
            throw new \Exception("There is no remote repositories that contain branch {$this->branchName} !");
        }

        return $filteredCodePathArray;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');
        $twoWay = $input->getOption('two-way');
        $this->branchName = $input->getArgument('branch-name');
        $this->useCurrentBranchNameAsLocal = $input->getOption('generate-branch-name');

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
            $this->fetchLatestDataFromRemoteBranch($alias, $repository, $this->branchName);

            $branchInfo = [];
            $localBranchName = $this->useCurrentBranchNameAsLocal ? $this->branchName :
                $codePath.'-'.$this->branchName;

            $this->execCmd("git branch --list {$localBranchName}", false, $branchInfo);
            if ($branchInfo) {
                $this->execCmd("git checkout {$localBranchName}", true);
            } else {
                /* We want make branch only from master */
                $this->execCmd("git checkout master", true);
                $this->execCmd("git checkout -b {$localBranchName}", true);
            }

            $this->updateSubtree($codePath, $twoWay, $this->branchName);
        }
    }
}
