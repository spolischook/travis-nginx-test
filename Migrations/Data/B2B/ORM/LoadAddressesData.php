<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2B\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadAddressesData extends AbstractFixture
{
    /** @var array ['country ISO2 code' => ['region code' => 'region combined code']] */
    private $regionByCountryMap;

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->loadData('addresses.csv');
        $manager->getClassMetadata('Oro\Bundle\AddressBundle\Entity\Address')->setLifecycleCallbacks([]);
        foreach ($data as $addressData) {
            $address = new Address();
            $created = $this->generateCreatedDate();
            $country = $this->getCountryReference($addressData['country code']);
            $address
                ->setCountry($country)
                ->setRegion($this->getRegionReference($addressData['country code'], $addressData['region code']))
                ->setCreated($created)
                ->setUpdated($this->generateUpdatedDate($created));
            $this->setObjectValues($address, $addressData);
            $manager->persist($address);
            $this->setAddressReference($addressData['uid'], $address);
        }
        $manager->flush();
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
     * {@inheritdoc}
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'day',
                'country code',
                'region code'
            ]
        );
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
