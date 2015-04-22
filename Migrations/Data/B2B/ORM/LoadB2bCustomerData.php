<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2B\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\SalesBundle\Entity\B2bCustomer;
use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadB2bCustomerData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\\LoadMainData',
            __NAMESPACE__ . '\\LoadAddressesData',
            __NAMESPACE__ . '\\LoadChannelData',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->loadData('customers/customers.csv');
        $manager->getClassMetadata('OroCRM\Bundle\SalesBundle\Entity\B2bCustomer')->setLifecycleCallbacks([]);

        foreach ($data as $customerData) {
            $customer = $this->createCustomer($customerData);
            $this->setB2bCustomerReference($customerData['uid'], $customer);
            $manager->persist($customer);
        }
        $manager->flush();

    }

    /**
     * @param array $customerData
     * @return B2bCustomer
     */
    protected function createCustomer(array $customerData)
    {
        $organization = $this->getOrganizationReference($customerData['organization uid']);
        $channel = $this->getDataChannel($customerData);
        $created = $this->generateCreatedDate();
        $shippingAddress = $this->getAddressReference($customerData['shipping address uid']);
        $contact = $this->getContactReference($customerData['contact uid']);

        $billingAddress = $customerData['shipping address uid'] === $customerData['billing address uid']
            ? clone $shippingAddress
            : $this->getAddressReference($customerData['billing address uid']);

        $customer = new B2bCustomer();
        $customer->setName($customerData['company']);
        $customer->setOwner($this->getUserReference($customerData['owner uid']));
        $customer->setContact($contact);
        $customer->setAccount($contact->getAccounts()->first());
        $customer->setOrganization($organization);
        $customer->setShippingAddress($shippingAddress);
        $customer->setBillingAddress($billingAddress);
        $customer->setCreatedAt($created);
        $customer->setUpdatedAt($this->generateUpdatedDate($created));

        if ($channel) {
            $customer->setDataChannel($channel);
        }
        return $customer;
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
