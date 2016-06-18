<?php

namespace Oro\Cli\Command\Repository;

use Oro\Git\VersionMatcher;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Cli\Command\RootCommand;

abstract class AbstractSync extends RootCommand
{
    /**
     * @var array list of supported upstream repositories mapped to local subtrees
     */
    protected $repositories = [
        'application/platform'       => 'git@github.com:laboro/platform-application.git',
        'application/crm'            => 'git@github.com:laboro/crm-application.git',
        'application/crm-enterprise' => 'git@github.com:laboro/crm-enterprise-application.git',
        'application/commerce'       => 'git@github.com:laboro/commerce-application.git',

        'package/platform'               => 'git@github.com:laboro/platform.git',
        'package/platform-enterprise'    => 'git@github.com:laboro/platform-enterprise.git',
        'package/crm'                    => 'git@github.com:laboro/crm.git',
        'package/crm-enterprise'         => 'git@github.com:laboro/crm-enterprise.git',
        'package/commerce'               => 'git@github.com:laboro/commerce.git',
        'package/dotmailer'              => 'git@github.com:laboro/OroCRMDotmailerBundle.git',
        'package/ldap'                   => 'git@github.com:laboro/OroCRMProLDAPBundle.git',
        'package/mailchimp'              => 'git@github.com:laboro/OroCRMMailChimpBundle.git',
        'package/magento-abandoned-cart' => 'git@github.com:laboro/OroCRMAbandonedCartBundle.git',
        'package/google-hangout'         => 'git@github.com:laboro/OroCRMHangoutsCallBundle.git',
        'package/serialized-fields'      => 'git@github.com:laboro/OroEntitySerializedFieldsBundle.git',
        'package/demo-data'              => 'git@github.com:laboro/OroCRMProDemoDataBundle.git',
        'package/zendesk'                => 'git@github.com:laboro/OroCRMZendeskBundle.git',
        'package/magento-contact-us'     => 'git@github.com:laboro/OroCRMMagentoContactUsBundle.git',
        'package/task'                   => 'git@github.com:laboro/OroCRMTaskBundle.git',

        'documentation' => 'git@github.com:orocrm/documentation.git',
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to subtree folder')
            ->addOption(
                'two-way',
                null,
                InputOption::VALUE_NONE,
                'Whether the synchronization of upstream repositories is needed'
            );
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
     * @param InputInterface $input
     *
     * @return string[]
     *
     * @throws \InvalidArgumentException if invalid path is specified
     */
    protected function getRepositories(InputInterface $input)
    {
        $path = $input->getArgument('path');
        if ($path) {
            if (!isset($this->repositories[$path])) {
                throw new \InvalidArgumentException(
                    "There are no repository registered for the \"{$path}\" path."
                );
            }
            $repositories = [$path => $this->repositories[$path]];
        } else {
            $repositories = $this->getAllRepositories($input);
        }

        return $repositories;
    }

    /**
     * @param InputInterface $input
     *
     * @return string[]
     */
    protected function getAllRepositories(InputInterface $input)
    {
        return $this->repositories;
    }

    /**
     * @param string $codePath
     *
     * @return string
     */
    protected function getAlias($codePath)
    {
        return $codePath . '_upstream';
    }

    /**
     * Gets aliases of remote repositories
     *
     * @return string[]
     */
    protected function getRemoteAliases()
    {
        $remotes = [];
        $this->execCmd('git remote', true, $remotes);

        return $remotes;
    }

    /**
     * @param string $alias
     * @param string $repository
     * @param bool $fetchOnlyMaster
     *
     */
    protected function fetchLatestDataFromRemoteBranch($alias, $repository, $fetchOnlyMaster = true)
    {
        $remotes = $this->getRemoteAliases();
        if (!in_array($alias, $remotes, true)) {
            /* Add remote repository if it was not added yet */
            $this->execCmd("git remote add --no-tags {$alias} {$repository}");
        }

        $fetchCommand = "git fetch --prune {$alias}";
        if ($fetchOnlyMaster) {
            $fetchCommand .= ' master';
        }

        $this->execCmd($fetchCommand);
    }

    /**
     * @param string $codePath
     * @param bool   $twoWay
     * @param string $branchName
     */
    protected function updateSubtree($codePath, $twoWay, $branchName = 'master')
    {
        $alias = $this->getAlias($codePath);
        /* Add subtree prefix for remote master */
        $this->execCmd("git subtree add --prefix={$codePath} {$alias} $branchName", false);

        /* Pull all updates from remote master */
        if ($this->execCmd("git subtree pull --prefix={$codePath} {$alias} $branchName") && $twoWay) {
            $this->assertGitVersion();

            /* Push all subtree changes to remote upstream repository */
            $this->execCmd("git subtree push --prefix={$codePath} {$alias} $branchName");
        }
    }


    protected function assertGitVersion()
    {
        $output = [];

        $this->execCmd('git --version', true, $output);

        $output = reset($output);

        $version = VersionMatcher::match($output);

        if (VersionMatcher::gte($version, '2.0.0')) {
            throw new \RuntimeException(
                'Git 2.* pushes full history to packages, use Git 1.9 instead. ' .
                'See https://magecore.atlassian.net/browse/BAP-10262 for details'
            );
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param array           $repositories
     */
    public function processSync(InputInterface $input, OutputInterface $output, $repositories)
    {
        /* Changing directory as subtree commands must be executed from the repository root */
        $currentDir = getcwd();
        chdir($this->getRootDir());
        try {
            $this->doSync($input, $output, $repositories);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
        /* Restore original directory */
        chdir($currentDir);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param string[]        $repositories
     *
     */
    abstract protected function doSync(InputInterface $input, OutputInterface $output, array $repositories);

    /**
     * Executes an external program.
     *
     * @param string $cmd            The command line
     * @param bool   $throwException Whether to throw exception in case command failed
     * @param array  $output         The command output
     *
     * @return bool The execution status. TRUE if no errors; otherwise, FALSE.
     */
    protected function execCmd($cmd, $throwException = true, array &$output = [])
    {
        $returnCode = 0;

        exec($cmd, $output, $returnCode);

        if ($throwException && $returnCode) {
            throw new \RuntimeException(
                sprintf(
                    'The "%s" command failed. Return code: %s. Error message:' . "\n"
                    . implode("\n", $output) . "\n"
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
}
