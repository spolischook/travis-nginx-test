<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Functional\Fixture;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LoadOrganizationUsersData extends AbstractFixture
{
    /** @var ObjectManager */
    protected $em;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->em = $manager;
        $this->createOrganization();
        $this->createUser();
    }

    protected function createOrganization()
    {
        $organization = new Organization();
        $organization->setEnabled(true);
        $organization->setName('TestOrg');

        $this->em->persist($organization);
        $this->em->flush();

        $this->setReference('test_organization', $organization);
    }

    protected function createUser()
    {
        $user = new User();
        $user->setFirstName('test');
        $user->setLastName('user');
        $user->setUsername('test.user');
        $user->setPassword('password');
        $user->setEmail('test.user@email.com');
        $user->setOrganization($this->em->getRepository('OroOrganizationBundle:Organization')->getFirst());
        $user->addOrganization($this->getReference('test_organization'));

        $this->em->persist($user);
        $this->em->flush();
    }
}
