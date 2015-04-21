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
    /** @var array ['country ISO2 code' => ['region code' => 'region combined code']] */
    private $regionByCountryMap;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\\LoadMainData',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data['customers'] = $this->getData();
        $companies = [];

        foreach ($data['customers'] as $customerData) {
            if (!isset($companies[$customerData['Company']])) {
                $organization = $this->getOrganizationReference($customerData['organization uid']);
                $channel = $this->getChannel($customerData['channel uid']);
                $customer = $this->createCustomer($organization, $data, $channel);

                $manager->persist($customer);

                $companies[$data['Company']] = $data['Company'];
            }
        }
        $manager->flush();

    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'customers' => $this->loadData('customers.csv'),
        ];
    }

    protected function createCustomer(Organization $organization, $data, Channel $channel = null)
    {
        $address  = new Address();
        $customer = new B2bCustomer();

        $customer->setName($data['Company']);
        $customer->setOwner($this->getUserReference($data['user uid']));
        $customer->setAccount($this->getAccountReference($data['account uid']));
        $customer->setOrganization($organization);

        $address->setCity($data['City']);
        $address->setStreet($data['StreetAddress']);
        $address->setPostalCode($data['ZipCode']);
        $address->setFirstName($data['GivenName']);
        $address->setLastName($data['Surname']);

        $address->setCountry($this->getCountryReference($data['Country']));
        $address->setRegion($this->getRegionReference($data['Country'], $data['State']));

        $customer->setShippingAddress($address);
        $customer->setBillingAddress(clone $address);

        if ($channel) {
            $customer->setDataChannel($channel);
        }

        return $customer;
    }

    protected function getChannel($channelUid)
    {
        $channel = null;
        if ($channelUid) {
            $channel = $this->getIntegrationDataChannelReference($channelUid);
        }
        return $channel;
    }

    /**
     * @param string $code ISO2 code
     *
     * @return Country
     */
    protected function getCountryReference($code)
    {
        return $this->em->getReference('OroAddressBundle:Country', $code);
    }

    /**
     * @param string $countryCode ISO2 code
     * @param string $code        region code
     *
     * @return null|Region
     */
    protected function getRegionReference($countryCode, $code)
    {
        if (null === $this->regionByCountryMap) {
            $this->regionByCountryMap = $this->loadRegionByCountryMap();
        }

        return isset($this->regionByCountryMap[$countryCode], $this->regionByCountryMap[$countryCode][$code])
            ?
            $this->em->getReference('OroAddressBundle:Region', $this->regionByCountryMap[$countryCode][$code])
            :
            null;
    }

    /**
     * @return array
     */
    private function loadRegionByCountryMap()
    {
        $items = $this->em->createQueryBuilder()
            ->from('OroAddressBundle:Country', 'c')
            ->leftJoin('c.regions', 'r')
            ->select(['c.iso2Code', 'r.code', 'r.combinedCode'])
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($items as $item) {
            $map[$item['iso2Code']] = isset($map[$item['iso2Code']]) ? $map[$item['iso2Code']] : [];

            if (isset($item['code'], $item['combinedCode'])) {
                $map[$item['iso2Code']][$item['code']] = $item['combinedCode'];
            }
        }

        return $map;
    }
}
