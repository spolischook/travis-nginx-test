<?php

namespace OroPro\Bundle\OrganizationBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LoadGlobalOrganization extends AbstractFixture
{
    const GLOBAL_ORGANIZATION_NAME = 'Global organization';

    public function load(ObjectManager $manager)
    {
        $organization = new Organization();
        $organization->setEnabled(true)
            ->setName(self::GLOBAL_ORGANIZATION_NAME)
            ->setIsGlobal(true);
        $manager->persist($organization);
        $manager->flush();
    }
}
