<?php

namespace Oro\Cli\Command\Repository;

use Oro\Cli\Command\RootCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class Sync extends RootCommand
{
    /**
     * @var array list of supported upstream repositories mapped to local subtrees
     */
    protected $repositories = array(
        'application/platform'          => 'git@github.com:laboro/platform-application.git',
        'application/crm'               => 'git@github.com:laboro/crm-application.git',
        'application/crm-enterprise'    => 'git@github.com:laboro/crm-enterprise-application.git',
        'application/commerce'          => 'git@github.com:laboro/b2b-dev.git',

        'package/platform'              => 'git@github.com:laboro/platform.git',
        'package/crm'                   => 'git@github.com:laboro/crm.git',
        'package/crm-enterprise'        => 'git@github.com:laboro/crm-enterprise.git',
        'package/commerce'              => 'git@github.com:laboro/b2b.git',
        'package/dotmailer'             => 'git@github.com:laboro/OroCRMDotmailerBundle.git',
        'package/ldap'                  => 'git@github.com:laboro/OroCRMProLDAPBundle.git',
        'package/mailchimp'             => 'git@github.com:laboro/OroCRMMailChimpBundle.git',
        'package/magento-abandoned-cart'=> 'git@github.com:laboro/OroCRMAbandonedCartBundle.git',
        'package/google-hangout'        => 'git@github.com:laboro/OroCRMHangoutsCallBundle.git',
        'package/serialized-fields'     => 'git@github.com:laboro/OroEntitySerializedFieldsBundle.git',
        'package/demo-data'             => 'git@github.com:laboro/OroCRMProDemoDataBundle.git',
        'package/zendesk'               => 'git@github.com:laboro/OroCRMZendeskBundle.git',
        'package/magento-contact-us'    => 'git@github.com:laboro/OroCRMMagentoContactUsBundle.git',

        'documentation'                 => 'git@github.com:orocrm/documentation.git',
    );
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('repository:sync')
            ->addArgument('path', InputArgument::OPTIONAL, 'Path to subtree folder')
            ->setDescription('Synchronize repository subtrees with upstream application and package repositories.')
            ->addUsage('application/crm')
            ->addUsage('package/platform');
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path  = $input->getArgument('path');

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

        $remotes = [];
        exec('git remote', $remotes);

        foreach ($repositories as $codePath => $repository) {
            $alias = $codePath . '_upstream';
            $output->writeln("Working on {$codePath} subtree from {$repository} repository.");
            if (in_array($alias, $remotes)) {
                /* Fetch master from remote */
                exec("git fetch {$alias} master");
            } else {
                /* Add remote repository if it was not added yet */
                exec("git remote add -f {$alias} {$repository}");
            }

            /* Add subtree prefix for remote master */
            exec("git subtree add --prefix={$codePath} {$alias} master");

            /* Pull all updates from remote master */
            exec("git subtree pull --prefix={$codePath} {$alias} master");

            /* Push all subtree changes to remote upstream repository */
            exec("git subtree push --prefix={$codePath} {$alias} master");
        }
        chdir($currentDir);
    }
}