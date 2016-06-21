<?php

namespace OroB2BPro\Bundle\AccountBundle\Migrations\Data\Demo\ORM;

use OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadWebsiteDefaultRoles as BaseLoadWebsiteDefaultRoles;

use OroB2BPro\Bundle\WebsiteBundle\Migrations\Data\Demo\ORM\LoadWebsiteDemoData;

class LoadWebsiteDefaultRoles extends BaseLoadWebsiteDefaultRoles
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadWebsiteDemoData::class];
    }
}
