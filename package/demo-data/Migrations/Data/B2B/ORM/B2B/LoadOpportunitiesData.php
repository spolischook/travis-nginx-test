<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2B\ORM\B2B;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

use OroCRM\Bundle\SalesBundle\Entity\Opportunity;
use OroCRM\Bundle\SalesBundle\Entity\OpportunityStatus;
use OroCRM\Bundle\SalesBundle\Entity\OpportunityCloseReason;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadOpportunitiesData extends AbstractFixture implements OrderedFixtureInterface
{
    const DEFAULT_OPPORTUNITY_STATUS = 'in_progress';
    const WON_OPPORTUNITY_STATUS     = 'won';
    const LOST_OPPORTUNITY_STATUS    = 'lost';

    /** @var OpportunityCloseReason[] */
    protected $closeReasons;

    /** @var OpportunityStatus[] */
    protected $statuses;

    /** @var AbstractEnumValue[] */
    protected $states;

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            'opportunities' => $this->loadData('b2b/opportunities.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        $manager->getClassMetadata('OroCRM\Bundle\SalesBundle\Entity\Opportunity')->setLifecycleCallbacks([]);
        foreach ($data['opportunities'] as $opportunityData) {
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
        $contact  = $this->getContactReference($opportunityData['contact uid']);
        $customer = $this->getCustomerReference($opportunityData['customer uid']);
        $created  = $this->generateCreatedDate();
        /** @var Organization $organization */
        $organization = $user->getOrganization();
        $status       = $this->getStatus($opportunityData['status']);

        $opportunity = new Opportunity();
        $updated     = $this->generateUpdatedDate($created);
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
            $opportunity->setDataChannel($this->getChannelReference($opportunityData['channel uid']));
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
                'budget amount',
                'state'
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
        $this->closeReasons      = array_reduce(
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
     * @param Opportunity       $opportunity
     * @param AbstractEnumValue $status
     */
    protected function setOpportunityStatus(Opportunity $opportunity, AbstractEnumValue $status)
    {
        $opportunity->setStatus($status);
        switch ($status->getId()) {
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

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 54;
    }

    /**
     * @param $id
     * @return AbstractEnumValue
     */
    protected function getStatus($id)
    {
        $states = $this->getStatuses();

        return isset($states[$id])
            ? $states[$id]
            : $states[self::DEFAULT_OPPORTUNITY_STATUS];
    }

    /**
     * @return AbstractEnumValue[]
     */
    protected function getStatuses()
    {
        if (count($this->states) === 0) {
            $this->loadStatuses();
        }

        return $this->states;
    }

    protected function loadStatuses()
    {
        $oppStateClassName = ExtendHelper::buildEnumValueClassName(Opportunity::INTERNAL_STATUS_CODE);
        $opportunityStates = $this->em->getRepository($oppStateClassName)->findAll();
        $this->states      = array_reduce(
            $opportunityStates,
            function ($states, $state) {
                /** @var AbstractEnumValue $state */
                $states[$state->getId()] = $state;

                return $states;
            },
            []
        );
    }
}
