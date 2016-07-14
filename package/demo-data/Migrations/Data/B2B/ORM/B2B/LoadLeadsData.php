<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2B\ORM\B2B;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

use OroCRM\Bundle\SalesBundle\Entity\Lead;
use OroCRM\Bundle\SalesBundle\Entity\LeadAddress;
use OroCRM\Bundle\SalesBundle\Entity\LeadEmail;
use OroCRM\Bundle\SalesBundle\Entity\LeadPhone;
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
                'campaign uid',
                'phonenumber',
                'email'
            ]
        );
    }

    /**
     * {@inheritdoc}
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
     * @SuppressWarnings(PHPMD.NPathComplexity)
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
        $lead = new Lead();
        $lead->setSource($this->getLeadSourceReference($leadData['lead source uid']));

        if (!empty($leadData['customer uid'])) {
            $customer = $this->getCustomerReference($leadData['customer uid']);
            $lead->setCustomer($customer);
            $this->addAddress($lead, $customer);
        }

        if (!empty($leadData['contact uid'])) {
            $contact = $this->getContactReference($leadData['contact uid']);
            $lead->setContact($contact);
        }

        if (!empty($leadData['channel uid'])) {
            $lead->setDataChannel($this->getChannelReference($leadData['channel uid']));
        }
        if (!empty($leadData['campaign uid'])) {
            $lead->setCampaign($this->getCampaignReference($leadData['campaign uid']));
        }
        if (!empty($leadData['phonenumber'])) {
            $leadPhone = new LeadPhone($leadData['phonenumber']);
            $leadPhone->setPrimary(true);
            $lead->addPhone($leadPhone);
        }
        if (!empty($leadData['email'])) {
            $leadEmail = new LeadEmail($leadData['email']);
            $leadEmail->setPrimary(true);
            $lead->addEmail($leadEmail);
        }

        $lead->setStatus($status)
            ->setOwner($user)
            ->setOrganization($organization)
            ->setCreatedAt($created)
            ->setUpdatedAt($this->generateUpdatedDate($created));

        $this->setObjectValues($lead, $leadData);
        $lead->setName($lead->getCompanyName());

        return $lead;
    }

    protected function addAddress(Lead $lead, B2bCustomer $customer)
    {
        if ($customer->getBillingAddress()) {
            $customerAddress = $customer->getBillingAddress();
            $leadAddress = new LeadAddress();
            //take name data from lead itself
            $leadAddress->setNamePrefix($lead->getNamePrefix());
            $leadAddress->setNameSuffix($lead->getNameSuffix());
            $leadAddress->setFirstName($lead->getFirstName());
            $leadAddress->setLastName($lead->getLastName());
            $leadAddress->setMiddleName($lead->getMiddleName());

            $leadAddress->setLabel($customerAddress->getLabel());
            $leadAddress->setOrganization($customerAddress->getOrganization());
            $leadAddress->setStreet($customerAddress->getStreet());
            $leadAddress->setStreet2($customerAddress->getStreet2());
            $leadAddress->setRegion($customerAddress->getRegion());
            $leadAddress->setCountry($customerAddress->getCountry());
            $leadAddress->setCity($customerAddress->getCity());
            $leadAddress->setPostalCode($customerAddress->getPostalCode());
            $leadAddress->setPrimary(true);
            $lead->addAddress($leadAddress);
        }
    }

    /**
     * @param ObjectManager $manager
     * @return array
     */
    protected function loadLeadStatuses(ObjectManager $manager)
    {
        $leadStatusClassName = ExtendHelper::buildEnumValueClassName(Lead::INTERNAL_STATUS_CODE);
        $leadStatuses = $manager->getRepository($leadStatusClassName)->findAll();

        return array_reduce(
            $leadStatuses,
            function ($statuses, $status) {
                $statuses[$status->getId()] = $status;

                return $statuses;
            },
            []
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 53;
    }
}
