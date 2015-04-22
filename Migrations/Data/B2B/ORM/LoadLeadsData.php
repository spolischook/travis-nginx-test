<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2B\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use OroCRM\Bundle\SalesBundle\Entity\B2bCustomer;
use OroCRM\Bundle\SalesBundle\Entity\LeadStatus;
use OroCRM\Bundle\SalesBundle\Entity\Lead;
use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadLeadsData extends AbstractFixture implements DependentFixtureInterface
{
    const DEFAULT_LEAD_STATUS = 'new';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\\LoadLeadSourceData',
            __NAMESPACE__ . '\\LoadChannelData',
            __NAMESPACE__ . '\\LoadMainData'
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->loadData('leads/leads.csv');
        $statuses = $this->loadLeadStatuses($manager);
        $manager->getClassMetadata('OroCRM\Bundle\SalesBundle\Entity\Lead')->setLifecycleCallbacks([]);
        foreach ($data as $leadData) {
            $user = $this->getUserReference($leadData['owner uid']);
            $this->setSecurityContext($user);

            $lead = $this->createLead($leadData, $statuses, $user);
            $manager->persist($lead);
        }
        $manager->flush();
    }

    /**
     * @param array $leadData
     * @param array $statuses
     * @param User $user
     * @return Lead
     */
    protected function createLead(array $leadData, array $statuses, User $user)
    {
        $status = isset($statuses[$leadData['status name']])
            ? $statuses[$leadData['status name']]
            : $statuses[self::DEFAULT_LEAD_STATUS];
        $source = $this->getLeadSource($leadData);
        $channel = $this->getDataChannel($leadData);
        $created = $this->generateCreatedDate();
        $address = $this->getAddress($leadData);
        $customer = $this->getB2bCustomer($leadData);
        /** @var Organization $organization */
        $organization = $user->getOrganization();

        $lead = new Lead();
        if ($source && method_exists($lead, 'setSource')) {
            $lead->setSource($source);
        }
        if ($channel) {
            $lead->setDataChannel($channel);
        }
        if ($address) {
            $lead->setAddress($address);
        }
        if ($customer) {
            $lead->setCustomer($customer);
        }
        $lead->setStatus($status)
            ->setName($leadData['companyname'])
            ->setOwner($user)
            ->setOrganization($organization)
            ->setCreatedAt($created)
            ->setUpdatedAt($this->generateUpdatedDate($created));
        $this->setObjectValues($lead, $leadData);
        return $lead;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'address uid',
                'lead source uid',
                'status name',
                'owner uid',
                'channel uid',
                'customer uid'
            ]
        );
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
     * @param array $leadData
     * @return null|\Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue
     */
    protected function getLeadSource(array $leadData)
    {
        if (!$leadData['lead source uid']) {
            return null;
        }
        return $this->getLeadSourceReference($leadData['lead source uid']);
    }

    /**
     * @param array $leadData
     * @return null|\Oro\Bundle\AddressBundle\Entity\Address
     */
    protected function getAddress(array $leadData)
    {
        if (!$leadData['address uid']) {
            return null;
        }
        return $this->getAddressReference($leadData['address uid']);
    }

    /**
     * @param array $leadData
     * @return null|B2bCustomer
     */
    protected function getB2bCustomer(array $leadData)
    {
        if (!$leadData['customer uid']) {
            return null;
        }
        return $this->getB2bCustomerReference($leadData['customer uid']);
    }

    /**
     * @param array $data
     * @return null|Channel
     */
    protected function getDataChannel(array $data = [])
    {
        $channel = null;
        if (array_key_exists('channel uid', $data)
            && $this->hasReference('Channel:' . $data['channel uid'])
        ) {
            $channel = $this->getReference('Channel:' . $data['channel uid']);
        }
        return $channel;
    }
}
