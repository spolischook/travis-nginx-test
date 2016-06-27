<?php

namespace Oro\Cli\Command\Repository;

use Oro\Cli\Command\RootCommand;
use Oro\Git\VersionMatcher;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractSync extends RootCommand
{
    use LoggerAwareTrait;

    /**
     * @var string[]|bool false if not initialized, use getter
     */
    private $repositories = false;

    /**
     * @var array[]|bool false if not initialized, use getter
     */
    private $branches = false;

    /** @var bool */
    private $dryRun = false;

    /** @var string */
    private $branch;

    /** @var string */
    private $path;

    /** @var bool */
    private $twoWay = false;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to subtree folder')
            ->addOption(
                'two-way',
                't',
                InputOption::VALUE_NONE,
                'Whether the synchronization of upstream repositories is needed'
            )
            ->addOption(
                'branch',
                'b',
                InputOption::VALUE_OPTIONAL,
                'Synchronize the specific branch of the monolithic repository subtrees with upstream repositories.',
                'master'
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Do not push to repository and upstreams'
            )
            ->addUsage('application/crm')
            ->addUsage('package/platform')
            ->addUsage('package/platform --two-way')
            ->addUsage('package/platform --branch=1.9')
            ->addUsage('package/platform --branch=1.9 --two-way --dry-run');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setLogger(new ConsoleLogger($output));

        $this->assertWorkingTreeEmpty();

        $this->branch = (string)$input->getOption('branch');
        $this->path = (string)$input->getArgument('path');
        $this->dryRun = (bool)$input->getOption('dry-run');
        $this->twoWay = (bool)$input->getOption('two-way');

        if ($this->isDryRun()) {
            $this->logger->critical('Actions not performed with --dry-run option');
        }

        $this->checkoutBranch($this->getBranch());

        $this->processSync($input, $output);
    }

    /** @return boolean */
    public function isDryRun()
    {
        return $this->dryRun;
    }

    /** @return string */
    public function getBranch()
    {
        return $this->branch;
    }

    /** @return string */
    public function getPath()
    {
        return $this->path;
    }

    /** @return boolean */
    public function isTwoWay()
    {
        return $this->twoWay;
    }

    /**
     * @throws \RuntimeException
     */
    protected function assertWorkingTreeEmpty()
    {
        $untrackedFiles = [];
        $this->execCmd('git status --porcelain', true, $untrackedFiles);
        if (!empty($untrackedFiles)) {
            throw new \RuntimeException(
                'There are untracked files in the working tree, to continue please clean the working tree.' . "\n"
                . 'Use "git status" command to see details.'
            );
        }
    }

    /**
     * @return string[]
     */
    protected function getRepositories()
    {
        if ($this->repositories === false) {
            $config = Yaml::parse(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'configuration.yml'));

            $this->repositories = $config['repositories'];
        }

        return $this->repositories;
    }

    /**
     * @return string[]
     */
    protected function getApplicableRepositories()
    {
        $repositories = $this->getRepositories();

        $baseBranch = $this->getBranch();
        $branches = $this->getBranches();
        if (!empty($branches[$baseBranch])) {
            $repositories = array_intersect_key($repositories, $branches[$baseBranch]);
        }

        if ($this->getPath()) {
            $repositories = array_intersect_key($repositories, array_flip([$this->getPath()]));

            foreach ($repositories as $prefix => $repository) {
                $this->logger->notice("{$prefix}: {$repository}");
            }
        }

        return $repositories;
    }

    /**
     * @return string[]
     */
    protected function getBranches()
    {
        if ($this->branches === false) {
            $config = Yaml::parse(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'configuration.yml'));

            $this->branches = $config['branches'];
        }

        return $this->branches;
    }

    /**
     * @param string $codePath
     *
     * @return string
     */
    protected function getRemoteAlias($codePath)
    {
        return $codePath . '_upstream';
    }

    /**
     * @param string $alias
     * @param string $repository
     * @param string $branch
     *
     * @return bool
     */
    protected function fetchLatestDataFromRemoteBranch($repository, $alias, $branch)
    {
        $this->execCmd("git remote add --no-tags {$alias} {$repository}", false);

        return $this->execCmd("git fetch --prune {$alias} {$branch}", false);
    }

    /**
     * @param string $repository
     * @param string $codePath
     * @param string $branchName
     */
    protected function pullSubtree($repository, $codePath, $branchName)
    {
        $this->logger->info("Working on \"{$codePath}\" subtree from \"{$repository}\" repository.");

        $remoteBranch = $this->resolveRemoteBranch($branchName, $codePath);
        $remoteAlias = $this->getRemoteAlias($codePath);

        $remoteBranchExists = $this->fetchLatestDataFromRemoteBranch($repository, $remoteAlias, $remoteBranch);

        if (!$remoteBranchExists) {
            $this->logger->alert("Branch {$remoteBranch} not found in $remoteAlias({$repository})");

            return;
        }

        $this->execCmd("git subtree pull --prefix={$codePath} {$remoteAlias} {$remoteBranch}");
    }

    /**
     * @param string $repository
     * @param string $codePath
     * @param string $branchName
     */
    protected function pushSubtree($repository, $codePath, $branchName)
    {
        $this->logger->info("Working on \"{$codePath}\" subtree from \"{$repository}\" repository.");

        $this->assertGitVersion();

        $remoteBranch = $this->resolveRemoteBranch($branchName, $codePath);
        $remoteAlias = $this->getRemoteAlias($codePath);

        $subtreeBranch = $this->getSubtreeBranch($codePath);

        if (!$this->hasLock($this->execCmd("git rev-parse {$subtreeBranch}"))) {
            $subtreeHash = $this->execCmd("git subtree split --prefix={$codePath} --branch={$subtreeBranch}");
            $this->putLock($subtreeHash);
        }

        $this->updateRemote($subtreeBranch, $remoteBranch, $remoteAlias);
    }

    /**
     * @param $commitHash
     * @return bool
     */
    protected function hasLock($commitHash)
    {
        return file_exists(sys_get_temp_dir() . DIRECTORY_SEPARATOR . $commitHash . DIRECTORY_SEPARATOR . '.lock');
    }

    /**
     * @param $commitHash
     */
    protected function putLock($commitHash)
    {
        file_put_contents(sys_get_temp_dir() . DIRECTORY_SEPARATOR . $commitHash . DIRECTORY_SEPARATOR . '.lock', '');
    }

    protected function assertGitVersion()
    {
        $output = [];

        $this->execCmd('git --version', true, $output);

        $version = VersionMatcher::match($output);

        if (VersionMatcher::gte($version, '2.0.0')) {
            throw new \RuntimeException(
                'Git 2.* pushes full history to packages, use Git 1.9 instead. ' .
                'See https://magecore.atlassian.net/browse/BAP-10262 for details'
            );
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function processSync(InputInterface $input, OutputInterface $output)
    {
        /* Changing directory as subtree commands must be executed from the repository root */
        $currentDir = getcwd();
        chdir($this->getRootDir());
        try {
            $this->doSync($input, $output);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $e->getTrace());
        }
        /* Restore original directory */
        chdir($currentDir);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     */
    abstract protected function doSync(InputInterface $input, OutputInterface $output);

    /**
     * Executes an external program.
     *
     * @param string $cmd The command line
     * @param bool $throwException Whether to throw exception in case command failed
     * @param array $output The command output
     *
     * @return bool The execution status. TRUE if no errors; otherwise, FALSE.
     */
    protected function execCmd($cmd, $throwException = true, array &$output = [])
    {
        $this->logger->info($cmd);
        if ($this->isDryRun()) {
            return true;
        }

        $process = new Process($cmd);
        $process
            ->setTimeout(0);

        $returnCode = $process->run(
            function ($level, $message) {
                $this->logger->debug($message);
            }
        );

        $output = $process->getOutput();

        if ($throwException && $returnCode) {
            throw new \RuntimeException(
                sprintf(
                    'The "%s" command failed. Return code: %s. Error message:' . "\n"
                    . $process->getErrorOutput() . "\n"
                    . 'Please fix the issue and run the "repository:sync" command again.' . "\n"
                    . 'The "git reset --hard" command can be used to rollback changes '
                    . 'made by "repository:sync" command or "git reset --hard origin/master" '
                    . 'to reset repository to the original state (note: local commits will be reverted as well!)',
                    $cmd,
                    $returnCode
                )
            );
        }

        return 0 == $returnCode;
    }

    /**
     * @param string $branchName
     */
    protected function checkoutBranch($branchName)
    {
        $this->execCmd("git fetch --prune origin {$branchName}", false);
        if ($this->execCmd("git checkout -f {$branchName}", false) === false) {
            $this->execCmd("git checkout -fb {$branchName}");
        }
    }

    /**
     * @param string $prefix
     * @return string
     */
    protected function getSubtreeBranch($prefix)
    {
        return $prefix . '_subtree';
    }

    /**
     * @param string $baseBranch
     * @param string $prefix
     * @return string
     */
    protected function resolveRemoteBranch($baseBranch, $prefix)
    {
        $remoteBranch = $baseBranch;
        $branches = $this->getBranches();
        if (!empty($branches[$baseBranch][$prefix])) {
            $remoteBranch = $branches[$baseBranch][$prefix];
        }

        $this->logger->notice("origin:{$baseBranch} => {$prefix}:{$remoteBranch}");

        return $remoteBranch;
    }

    /**
     * @param string $fromBranch
     * @param string $toBranch
     * @param string $remoteAlias
     */
    protected function updateRemote($fromBranch, $toBranch, $remoteAlias = 'origin')
    {
        $this->execCmd("git push {$remoteAlias} {$fromBranch}:{$toBranch}");
    }

    /**
     * @param string $branch
     * @param string $remoteAlias
     */
    protected function updateFromRemote($branch, $remoteAlias = 'origin')
    {
        $this->execCmd("git pull {$remoteAlias} {$branch}");
    }
}
