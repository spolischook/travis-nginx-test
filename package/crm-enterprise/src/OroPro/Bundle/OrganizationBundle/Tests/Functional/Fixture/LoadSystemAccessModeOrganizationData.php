<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Functional\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LoadSystemAccessModeOrganizationData extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $organization = new Organization();
        $organization->setName('test system access org');
        $organization->setEnabled(true);
        $organization->setIsGlobal(true);

        $manager->persist($organization);
        $manager->flush();
    }
}
