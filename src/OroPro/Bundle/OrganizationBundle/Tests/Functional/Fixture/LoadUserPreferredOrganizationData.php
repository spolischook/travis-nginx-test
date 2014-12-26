<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Functional\Fixture;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LoadUserPreferredOrganizationData extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $mainOrganization = $this->createOrganization();
        $organization     = $this->createOrganization();
        $user             = $this->createUser($mainOrganization);

        $manager->persist($mainOrganization);
        $manager->persist($organization);
        $manager->persist($user);
        $manager->flush();

        $this->setReference('user', $user);
        $this->setReference('mainOrganization', $mainOrganization);
        $this->setReference('organization', $organization);
    }

    /**
     * @return Organization
     */
    protected function createOrganization()
    {
        $organization = new Organization();
        $organization->setName(uniqid('organization', true));
        $organization->setEnabled(true);

        return $organization;
    }

    /**
     * @param Organization $organization
     *
     * @return User
     */
    protected function createUser(Organization $organization)
    {
        $user = new User();
        $user->setUsername(uniqid('username', true));
        $user->setPassword(uniqid('password', true));
        $user->setEmail(uniqid('email', true));
        $user->addOrganization($organization);
        $user->setOrganization($organization);

        return $user;
    }
}
