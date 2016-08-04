<?php

namespace OroCRM\Bundle\SalesBundle\Tests\Functional\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use OroCRM\Bundle\SalesBundle\Entity\Opportunity;

class LoadOpportunityStatusBoardFixtures extends AbstractFixture
{
    const OPPORTUNITY_COUNT = 20;
    const STATUSES_COUNT = 4;

    /** @var ObjectManager */
    protected $em;

    /** @var Organization */
    protected $organization;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->em = $manager;
        $this->organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
        $this->createOpportunities();
    }

    protected function createOpportunities()
    {
        $opportunityStatuses = ['in_progress', 'lost', 'needs_analysis', 'won'];
        for ($i = 0; $i < self::OPPORTUNITY_COUNT; $i++) {
            $opportunity = new Opportunity();
            $opportunity->setName('opname_' . $i);
            $opportunity->setBudgetAmount(50.00);
            $opportunity->setProbability(10);
            $opportunity->setOrganization($this->organization);
            $statusName = $opportunityStatuses[$i % self::STATUSES_COUNT];
            $enumClass = ExtendHelper::buildEnumValueClassName(Opportunity::INTERNAL_STATUS_CODE);
            $opportunity->setStatus($this->em->getReference($enumClass, $statusName));
            $this->em->persist($opportunity);
            $this->em->flush();
        }

        return $this;
    }
}
