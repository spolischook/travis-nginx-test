<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2B\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use OroCRM\Bundle\ChannelBundle\Entity\Channel;
use OroCRM\Bundle\SalesBundle\Entity\Opportunity;

use OroCRM\Bundle\SalesBundle\Entity\OpportunityCloseReason;
use OroCRM\Bundle\SalesBundle\Entity\OpportunityStatus;
use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadOpportunitiesData extends AbstractFixture implements DependentFixtureInterface
{
    const DEFAULT_OPPORTUNITY_STATUS = 'in_progress';
    const WON_OPPORTUNITY_STATUS = 'won';
    const LOST_OPPORTUNITY_STATUS = 'lost';

    /** @var OpportunityCloseReason[] */
    protected $closeReasons;

    /** @var OpportunityStatus[] */
    protected $statuses;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\\LoadLeadsData',
            __NAMESPACE__ . '\\LoadB2bCustomerData',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->loadData('leads/opportunities.csv');
        $manager->getClassMetadata('OroCRM\Bundle\SalesBundle\Entity\Opportunity')->setLifecycleCallbacks([]);
        foreach ($data as $opportunityData) {
            $user = $this->getUserReference($opportunityData['user uid']);
            $this->setSecurityContext($user);
            $opportunity = $this->createOpportunity($opportunityData, $user);
            $this->setOpportunityReference($opportunityData['uid'], $opportunity);
            $manager->persist($opportunity);
        }
        $manager->flush();
    }

    protected function createOpportunity(array $opportunityData, User $user)
    {
        $contact     = $this->getContactReference($opportunityData['contact uid']);
        $customer    = $this->getB2bCustomerReference($opportunityData['customer uid']);
        $created = $this->generateCreatedDate();
        /** @var Organization $organization */
        $organization = $user->getOrganization();
        $status = $this->getStatus($opportunityData['status']);

        $opportunity = new Opportunity();
        $updated = $this->generateUpdatedDate($created);
        $opportunity->setName($contact->getFirstName() . ' ' . $contact->getLastName())
            ->setContact($contact)
            ->setOwner($user)
            ->setOrganization($organization)
            ->setCreatedAt($created)
            ->setUpdatedAt($updated)
            ->setCloseDate($this->generateCloseDate($updated))
            ->setCustomer($customer);

        if (!empty($opportunityData['budget amount'])) {
            $opportunity->setBudgetAmount($opportunityData['budget amount']);
        }

        if (!empty($opportunityData['channel uid'])) {
            $opportunity->setDataChannel($this->getDataChannelReference($opportunityData['channel uid']));
        }
        if (!empty($opportunityData['lead uid'])) {
            $opportunity->setLead($this->getLeadReference($opportunityData['lead uid']));
        }
        if (!empty($opportunityData['close reason'])
            && $closeReason = $this->getCloseReason($opportunityData['close reason'])
        ) {
            $opportunity->setCloseReason($closeReason);
        }

        $this->setObjectValues($opportunity, $opportunityData);
        $this->setOpportunityStatus($opportunity, $status);
        return $opportunity;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'user uid',
                'customer uid',
                'contact uid',
                'channel uid',
                'status',
                'lead uid',
                'close reason',
                'budget amount'
            ]
        );
    }

    /**
     * @param $name
     * @return null|OpportunityCloseReason
     */
    protected function getCloseReason($name)
    {
        $closeReasons = $this->getCloseReasons();
        return isset($closeReasons[$name])
            ? $closeReasons[$name]
            : null;
    }

    /**
     * @return OpportunityCloseReason[]
     */
    protected function getCloseReasons()
    {
        if (count($this->closeReasons) === 0) {
            $this->loadCloseReasons();
        }
        return $this->closeReasons;
    }

    protected function loadCloseReasons()
    {
        $opportunityCloseReasons = $this->em->getRepository('OroCRMSalesBundle:OpportunityCloseReason')->findAll();
        $this->closeReasons = array_reduce(
            $opportunityCloseReasons,
            function ($reasons, $reason) {
                /** @var OpportunityCloseReason $reason */
                $reasons[$reason->getName()] = $reason;
                return $reasons;
            },
            []
        );
    }

    /**
     * @param $name
     * @return OpportunityStatus
     */
    protected function getStatus($name)
    {
        $statuses = $this->getStatuses();
        return isset($statuses[$name])
            ? $statuses[$name]
            : $statuses[self::DEFAULT_OPPORTUNITY_STATUS];
    }

    /**
     * @return OpportunityStatus[]
     */
    protected function getStatuses()
    {
        if (count($this->statuses) === 0) {
            $this->loadStatuses();
        }
        return $this->statuses;
    }

    protected function loadStatuses()
    {
        $opportunityStatuses = $this->em->getRepository('OroCRMSalesBundle:OpportunityStatus')->findAll();
        $this->statuses = array_reduce(
            $opportunityStatuses,
            function ($statuses, $status) {
                /** @var OpportunityStatus $status */
                $statuses[$status->getName()] = $status;
                return $statuses;
            },
            []
        );
    }

    /**
     * @param Opportunity       $opportunity
     * @param OpportunityStatus $status
     */
    protected function setOpportunityStatus(Opportunity $opportunity, OpportunityStatus $status)
    {
        $opportunity->setStatus($status);
        switch ($status->getName()) {
            case self::WON_OPPORTUNITY_STATUS:
                $opportunity->setProbability(1);
                break;
            case self::LOST_OPPORTUNITY_STATUS:
                $opportunity->setProbability(0);
                break;
            default:
                break;
        }
    }
}
