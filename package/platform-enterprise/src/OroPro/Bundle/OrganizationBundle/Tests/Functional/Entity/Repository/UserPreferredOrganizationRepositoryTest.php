<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroPro\Bundle\OrganizationBundle\Entity\UserPreferredOrganization;

/**
 * @dbIsolation
 */
class UserPreferredOrganizationRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroPro\Bundle\OrganizationBundle\Tests\Functional\Fixture\LoadUserPreferredOrganizationData'
            ]
        );
    }

    public function testSavePreferredOrganization()
    {
        $user         = $this->getReference('user');
        $organization = $this->getReference('mainOrganization');

        $this->assertEquals(0, $this->getEntityCount($user));

        $repo = $this->getContainer()->get('doctrine')
            ->getRepository('OroProOrganizationBundle:UserPreferredOrganization');

        $repo->savePreferredOrganization($user, $organization);

        $this->assertEquals(1, $this->getEntityCount($user));

        /** @var UserPreferredOrganization $createdRecord */
        $createdRecord = $repo->findOneBy(['user' => $user]);

        $this->assertNotEmpty($createdRecord);
        $this->assertEquals($organization->getId(), $createdRecord->getOrganization()->getId());

        return $createdRecord;
    }

    /**
     * @depends testSavePreferredOrganization
     *
     * @param UserPreferredOrganization $preferredOrganization
     */
    public function testUpdatePreferredOrganization(UserPreferredOrganization $preferredOrganization)
    {
        $user         = $preferredOrganization->getUser();
        $organization = $this->getReference('organization');

        $this->assertEquals(1, $this->getEntityCount($user));

        $repo = $this->getContainer()->get('doctrine')
            ->getRepository('OroProOrganizationBundle:UserPreferredOrganization');
        $repo->updatePreferredOrganization($user, $organization);

        $this->assertEquals(1, $this->getEntityCount($user));

        $oldRecord = $repo->findOneBy(
            [
                'user'         => $user,
                'organization' => $preferredOrganization->getOrganization()
            ]
        );

        $this->assertEmpty($oldRecord);
    }

    /**
     * @param User $user
     *
     * @return int
     */
    protected function getEntityCount(User $user)
    {
        $em = $this->getEntityManager();

        $qb = $em->createQueryBuilder()
            ->select($em->getExpressionBuilder()->count('e'))
            ->from('OroProOrganizationBundle:UserPreferredOrganization', 'e')
            ->where('e.user = :user')
            ->setParameter('user', $user);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')
            ->getManager();
    }
}
