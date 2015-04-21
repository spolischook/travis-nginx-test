<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2B\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use OroCRM\Bundle\SalesBundle\Entity\B2bCustomer;
use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use OroCRM\Bundle\ChannelBundle\Entity\Channel;
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
            $organization = $this->getOrganizationReference($customerData['organization uid']);
            $channel = $this->getChannel($customerData);
            $created = $this->generateCreatedDate();

            $customer = new B2bCustomer();
            $shippingAddress = $this->getAddressReference($customerData['shipping address uid']);
            if ($customerData['shipping address uid'] === $customerData['billing address uid']) {
                $billingAddress = clone $shippingAddress;
            } else {
                $billingAddress = $this->getAddressReference($customerData['billing address uid']);
            }
            //$billingAddress = $this->getAddressReference($customerData['billing address uid']);
            $customer->setName($customerData['company']);
            $customer->setOwner($this->getUserReference($customerData['owner uid']));
            $customer->setAccount($this->getAccountReference($customerData['account uid']));
            $customer->setOrganization($organization);
            $customer->setShippingAddress($shippingAddress);
            $customer->setBillingAddress($billingAddress);
            $customer->setCreatedAt($created);
            $customer->setUpdatedAt($this->generateUpdatedDate($created));

            if ($channel) {
                $customer->setDataChannel($channel);
            }

            $manager->persist($customer);
        }

        $manager->flush();

    }

    protected function getChannel(array $channelData = [])
    {
        $channel = null;
        if (array_key_exists('channel uid', $channelData)) {
            $channel = $this->getIntegrationDataChannelReference($channelData['channel uid']);
        }
        return $channel;
    }
}
