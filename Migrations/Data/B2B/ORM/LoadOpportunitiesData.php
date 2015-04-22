<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2B\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use OroCRM\Bundle\SalesBundle\Entity\Opportunity;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadOpportunitiesData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . 'LoadLeadsData',
            __NAMESPACE__ . 'LoadB2bCustomerData',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->loadData('leads/opportunities.csv');
        foreach ($data as $opportunityData) {
            $user = $this->getUserReference($opportunityData['user uid']);
            $this->setSecurityContext($user);
            $opportunity = $this->createOpportunity($opportunityData, $user);
            $manager->persist($opportunity);
        }
        $manager->flush();
    }

    protected function createOpportunity(array $opportunityData, User $user)
    {
        $contact     = $this->getContactReference($opportunityData['contact uid']);
        $customer    = $this->getB2bCustomerReference($opportunityData['customer uid']);
        /** @var Organization $organization */
        $organization = $user->getOrganization();

        $opportunity = new Opportunity();
        $channel = $this->getDataChannel();
        $opportunity->setName($contact->getFirstName() . ' ' . $contact->getLastName())
            ->setContact($contact)
            ->setOwner($user)
            ->setOrganization($organization)
            ->setCustomer($customer);

        if ($channel) {
            $opportunity->setDataChannel($channel);
        }

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
                'contact uid'
            ]
        );
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
