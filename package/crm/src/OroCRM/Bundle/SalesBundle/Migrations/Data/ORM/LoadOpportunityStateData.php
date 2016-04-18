<?php

namespace OroCRM\Bundle\SalesBundle\Migrations\Data\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;

use OroCRM\Bundle\SalesBundle\Entity\Opportunity;

class LoadOpportunityStateData extends AbstractEnumFixture
{
    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var array
     */
    protected $statusMapping = [
        'won' => 'won',
        'lost' => 'lost',
        'in_progress' => 'solution_development'
    ];

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            'identification_alignment' => 'Identification & Alignment',
            'needs_analysis' => 'Needs Analysis',
            'solution_development' => 'Solution Development',
            'negotiation' => 'Negotiation',
            'won' => 'Closed Won',
            'lost' => 'Closed Lost'
        ];
    }

    /**
     * @return string
     */
    protected function getEnumCode()
    {
        return Opportunity::INTERNAL_STATE_CODE;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;
        $repository = $manager->getRepository('OroCRMSalesBundle:Opportunity');
        $connection = $repository->createQueryBuilder('o')->getEntityManager()->getConnection();
        $query = 'UPDATE orocrm_sales_opportunity as o SET o.state_id= ? WHERE o.status_name = ?';
        parent::load($manager);

        foreach ($this->statusMapping as $status => $state) {
            $connection->executeQuery($query, [$state, $status]);
        }
    }
}
