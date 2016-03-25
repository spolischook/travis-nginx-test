<?php

namespace Oro\Cli\Command\Repository;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use Oro\Cli\Command\RootCommand;

class Sync extends RootCommand
{
    /**
     * @var array list of supported upstream repositories mapped to local subtrees
     */
    protected $repositories = array(
        'application/platform'       => 'git@github.com:laboro/platform-application.git',
        'application/crm'            => 'git@github.com:laboro/crm-application.git',
        'application/crm-enterprise' => 'git@github.com:laboro/crm-enterprise-application.git',
        'application/commerce'       => 'git@github.com:laboro/commerce-dev.git',

        'package/platform'               => 'git@github.com:laboro/platform.git',
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

        'documentation' => 'git@github.com:orocrm/documentation.git',
    );

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('repository:sync')
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to subtree folder')
            ->addOption(
                'two-way',
                null,
                InputOption::VALUE_NONE,
                'Whether the synchronization of upstream repositories is needed'
            )
            ->setDescription('Synchronize repository subtrees with upstream application and package repositories.')
            ->addUsage('application/crm')
            ->addUsage('package/platform');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');
        $twoWay = $input->getOption('two-way');

        if ($path) {
            if (isset($this->repositories[$path])) {
                $repositories = array(
                    $path => $this->repositories[$path]
                );
            } else {
                $output->writeln("There are no repository registered for \"{$path}\" path.");

                return;
            }
        } else {
            $repositories = $this->repositories;
        }

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
     */
    protected function doSync(OutputInterface $output, array $repositories, $twoWay)
    {
        $remotes = [];
        $this->execCmd('git remote', true, $remotes);

        foreach ($repositories as $codePath => $repository) {
            $alias = $codePath . '_upstream';
            $output->writeln("Working on {$codePath} subtree from {$repository} repository.");
            if (in_array($alias, $remotes, true)) {
                /* Fetch master from remote */
                $this->execCmd("git fetch {$alias} master");
            } else {
                /* Add remote repository if it was not added yet */
                $this->execCmd("git remote add -f {$alias} {$repository}");
            }

            /* Add subtree prefix for remote master */
            $this->execCmd("git subtree add --prefix={$codePath} {$alias} master", false);

            /* Pull all updates from remote master */
            if ($this->execCmd("git subtree pull --prefix={$codePath} {$alias} master") && $twoWay) {
                /* Push all subtree changes to remote upstream repository */
                $this->execCmd("git subtree push --prefix={$codePath} {$alias} master");
            }
        }
    }
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
                    '<error>The "%s" command failed. Return code: %s.</error>' . "\n"
                    . '<error>Please fix the issue and'
                    . ' run the "repository:sync" command again.</error>' . "\n"
                    . '<error>The "git reset --hard origin/master" command can be used'
                    . ' to rollback changes made by "repository:sync" command.</error>',
                    $cmd,
                    $returnCode
                )
            );
        }

        return 0 == $returnCode;
    }
}
