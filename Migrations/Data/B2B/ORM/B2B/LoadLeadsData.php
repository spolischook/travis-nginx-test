<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2B\ORM\B2B;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

use OroCRM\Bundle\SalesBundle\Entity\Lead;
use OroCRM\Bundle\SalesBundle\Entity\LeadStatus;
use OroCRM\Bundle\SalesBundle\Entity\B2bCustomer;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadLeadsData extends AbstractFixture implements OrderedFixtureInterface
{
    const DEFAULT_LEAD_STATUS = 'new';

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'leads' => $this->loadData('b2b/leads.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'lead source uid',
                'status name',
                'user uid',
                'channel uid',
                'customer uid',
                'contact uid',
                'campaign uid'
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data     = $this->getData();
        $statuses = $this->loadLeadStatuses($manager);
        $manager->getClassMetadata('OroCRM\Bundle\SalesBundle\Entity\Lead')->setLifecycleCallbacks([]);
        foreach ($data['leads'] as $leadData) {
            $user = $this->getUserReference($leadData['user uid']);
            $this->setSecurityContext($user);

            $lead = $this->createLead($leadData, $statuses, $user);
            $this->setLeadReference($leadData['uid'], $lead);
            $manager->persist($lead);
        }
        $manager->flush();
    }

    /**
     * @param array $leadData
     * @param array $statuses
     * @param User  $user
     * @return Lead
     */
    protected function createLead(array $leadData, array $statuses, User $user)
    {
        $status  = isset($statuses[$leadData['status name']])
            ? $statuses[$leadData['status name']]
            : $statuses[self::DEFAULT_LEAD_STATUS];
        $created = $this->generateCreatedDate();
        /** @var Organization $organization */
        $organization = $user->getOrganization();
        $customer     = $this->getB2bCustomerReference($leadData['customer uid']);

        $lead = new Lead();
        $lead->setSource($this->getLeadSourceReference($leadData['lead source uid']));
        $lead->setCustomer($customer);
        if (!empty($leadData['channel uid'])) {
            $lead->setDataChannel($this->getChannelReference($leadData['channel uid']));
        }
        if (!empty($leadData['campaign uid'])) {
            $lead->setCampaign($this->getCampaignReference($leadData['campaign uid']));
        }
        $lead->setStatus($status)
            ->setOwner($user)
            ->setOrganization($organization)
            ->setCreatedAt($created)
            ->setUpdatedAt($this->generateUpdatedDate($created));
        $this->setObjectValues($lead, $leadData);
        $lead->setName($lead->getCompanyName());

        $this->addAddress($lead, $customer);

        return $lead;
    }

    protected function addAddress(Lead $lead, B2bCustomer $customer)
    {
        if ($customer->getBillingAddress()) {
            $lead->setAddress($customer->getBillingAddress());
        }
    }

    /**
     * @param ObjectManager $manager
     * @return array
     */
    protected function loadLeadStatuses(ObjectManager $manager)
    {
        $leadStatuses = $manager->getRepository('OroCRMSalesBundle:LeadStatus')->findAll();

        return array_reduce(
            $leadStatuses,
            function ($statuses, $status) {
                /** @var LeadStatus $status */
                $statuses[$status->getName()] = $status;

                return $statuses;
            },
            []
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 53;
    }
}
