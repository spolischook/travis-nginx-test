<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;

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
                'OroPro\Bundle\OrganizationBundle\Tests\Functional' .
                '\Entity\Repository\Fixtures\LoadUserPreferredOrganizationData'
            ]
        );
    }

    public function testSavePreferredOrganization()
    {
        $user = $this->getReference('user');

        $this->assertEquals(0, $this->getEntityCount());

        $this->getContainer()->get('doctrine')
            ->getRepository('OroProOrganizationBundle:UserPreferredOrganization')
            ->savePreferredOrganization($user, $this->getReference('mainOrganization'));

        $this->assertEquals(1, $this->getEntityCount());
    }

    /**
     * @return int
     */
    protected function getEntityCount()
    {
        $em = $this->getEntityManager();

        $qb = $em->createQueryBuilder()
            ->select($em->getExpressionBuilder()->count('e'))
            ->from('OroProOrganizationBundle:UserPreferredOrganization', 'e');

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
