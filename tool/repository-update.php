<?php
/**
 * Script updates repository code from upstream application and package remote repositories.
 */

/**
 * Array of repositories with path to subtree folder as key and repository URL as value.
 */
$repositories = array(
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
);

/* Read list of configured remote repositories */
$remotes = '';
exec('git remote', $remotes);

foreach ($repositories as $folder => $repository) {
    $alias = $folder;
    echo "\nWorking on {$folder} subtree from {$repository} repository \n";
    if (in_array($alias, $remotes)) {
        /* Fetch master from remote */
        system("git fetch {$alias} master");
    } else {
        /* Add remote repository if it was not added yet */
        system("git remote add -f {$alias} {$repository}");
    }

    /* Add subtree prefix for remote master */
    system("git subtree add --prefix={$folder} {$alias} master");

    /* Pull all updates from remote master */
    system("git subtree pull --prefix={$folder} {$alias} master");
}