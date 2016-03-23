<?php

namespace Oro\Cli\Command\Repository;

use Symfony\Component\Console\Output\OutputInterface;

use Oro\Cli\Command\RootCommand;

abstract class AbstractSync extends RootCommand
{
    /**
     * @var array Contains list of remote aliases
     */
    protected $remotes;

    /**
     * @var array list of supported upstream repositories mapped to local subtrees
     */
    protected $repositories = array(
        'application/platform' => 'git@github.com:laboro/platform-application.git',
        'application/crm' => 'git@github.com:laboro/crm-application.git',
        'application/crm-enterprise' => 'git@github.com:laboro/crm-enterprise-application.git',
        //        'application/commerce'       => 'git@github.com:laboro/b2b-dev.git',

        'package/platform' => 'git@github.com:laboro/platform.git',
        'package/crm' => 'git@github.com:laboro/crm.git',
        'package/crm-enterprise' => 'git@github.com:laboro/crm-enterprise.git',
        'package/commerce' => 'git@github.com:laboro/b2b.git',
        'package/dotmailer' => 'git@github.com:laboro/OroCRMDotmailerBundle.git',
        'package/ldap' => 'git@github.com:laboro/OroCRMProLDAPBundle.git',
        'package/mailchimp' => 'git@github.com:laboro/OroCRMMailChimpBundle.git',
        'package/magento-abandoned-cart' => 'git@github.com:laboro/OroCRMAbandonedCartBundle.git',
        'package/google-hangout' => 'git@github.com:laboro/OroCRMHangoutsCallBundle.git',
        'package/serialized-fields' => 'git@github.com:laboro/OroEntitySerializedFieldsBundle.git',
        'package/demo-data' => 'git@github.com:laboro/OroCRMProDemoDataBundle.git',
        'package/zendesk' => 'git@github.com:laboro/OroCRMZendeskBundle.git',
        'package/magento-contact-us' => 'git@github.com:laboro/OroCRMMagentoContactUsBundle.git',

        'documentation' => 'git@github.com:orocrm/documentation.git',
    );

    protected function getAlias($codePath)
    {
        return $codePath.'_upstream';
    }

    /**
     * Get list of remote aliases
     *
     * @return array
     */
    protected function getRemotes()
    {
        if (is_null($this->remotes)) {
            $this->remotes = [];
            $this->execCmd('git remote', true, $this->remotes);
        }

        return $this->remotes;
    }

    /**
     * @return array
     */
    protected function getRepositoriesIfPathIsNull()
    {
        return $this->repositories;
    }

    /**
     * @param string $path
     *
     * @return array
     * @throws \Exception
     */
    protected function getRepositoriesFromThePath($path)
    {
        if (is_null($path)) {
            return $this->getRepositoriesIfPathIsNull();
        }

        if (isset($this->repositories[$path])) {
            return array(
                $path => $this->repositories[$path],
            );
        }

        throw new \Exception("There are no repository registered for \"{$path}\" path.");
    }

    /**
     * @throws \Exception
     */
    protected function validateWorkingTree()
    {
        $untrackedFiles = [];
        $this->execCmd('git status --porcelain', true, $untrackedFiles);
        if (0 === count($untrackedFiles)) {
            throw new \Exception('There are untracked files in working tree, to continue please clean the working tree.');
        }
    }

    /**
     * @param string $alias
     * @param string $repository
     * @param string $branchName
     */
    protected function fetchLatestDataFromRemoteBranch($alias, $repository, $branchName = 'master')
    {
        $remotes = $this->getRemotes();
        if (!in_array($alias, $remotes, true)) {
            /* Add remote repository if it was not added yet */
            $this->execCmd("git remote add -f {$alias} {$repository}");
        }

        $this->execCmd("git fetch {$alias} {$branchName}");
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
            /* Push all subtree changes to remote upstream repository */
            $this->execCmd("git subtree push --prefix={$codePath} {$alias} $branchName");
        }
    }

    /**
     * @param OutputInterface $output
     * @param array           $repositories
     * @param bool            $twoWay
     */
    public function processSync($output, $repositories, $twoWay)
    {
        /* Changing directory as subtree commands must be executed from the repository root */
        $currentDir = getcwd();
        chdir($this->getRootDir());
        try {
            $this->doSync($output, $repositories, $twoWay);
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
        /* Restore original directory */
        chdir($currentDir);
    }

    /**
     * @param OutputInterface $output
     * @param array           $repositories
     * @param bool            $twoWay
     *
     */
    abstract protected function doSync(OutputInterface $output, array $repositories, $twoWay);

    /**
     * Executes an external program.
     *
     * @param string $cmd            The command line
     * @param bool   $throwException Whether to throw exception in case command failed
     * @param array  $output         The command output
     *
     * @return bool The execution status. TRUE if no errors; otherwise, FALSE.
     */
    protected function execCmd($cmd, $throwException = true, array &$output = array())
    {
        $returnCode = 0;

        exec($cmd, $output, $returnCode);

        if ($throwException && $returnCode) {
            throw new \RuntimeException(
                sprintf(
                    '<error>The "%s" command failed. Return code: %s.</error>'."\n"
                    .'<error>Please fix the issue and'
                    .' run the "repository:sync" command again.</error>'."\n"
                    .'<error>The "git reset --hard origin/master" command can be used'
                    .' to rollback changes made by "repository:sync" command.</error>',
                    $cmd,
                    $returnCode
                )
            );
        }

        return 0 == $returnCode;
    }
}