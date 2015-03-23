<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LoadBusinessUnitData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadOrganizationData',
        ];
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            'main' => $this->loadData('business_units/main_business_units.csv'),
            'children' => $this->loadData('business_units/children_business_units.csv'),
        ];
    }

    /**
     * Update and return Main Business Unit
     * @return BusinessUnit
     * @throws EntityNotFoundException
     */
    protected function getMainBusinessUnit()
    {
        $businessRepository = $this->em->getRepository('OroOrganizationBundle:BusinessUnit');

        /** @var BusinessUnit $mainBusinessUnit */
        $entity = $businessRepository->findOneBy(['name' => 'Main']);
        if (!$entity) {
            $entity = $businessRepository->find(1);
        }

        if (!$entity) {
            throw new EntityNotFoundException('Main business unit is not defined.');
        }
        return $entity;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        $main = true;
        foreach ($data['main'] as $mainBusinessUnitData) {
            if ($main) {
                $businessUnit = $this->getMainBusinessUnit();
                $main = false;
            } else {
                $businessUnit = new Organization();
            }

            $uid = $mainBusinessUnitData['uid'];
            $organization = $this->getOrganizationReference($mainBusinessUnitData['organization uid']);
            unset($mainBusinessUnitData['uid'], $mainBusinessUnitData['organization uid']);
            $this->setObjectValues($businessUnit, $mainBusinessUnitData);
            $businessUnit->setOrganization($organization);
            $this->setReference('BusinessUnit:' . $uid, $businessUnit);
            $manager->persist($businessUnit);
        }

        foreach ($data['children'] as $businessUnitData) {
            $mainBusinessUnit = $this->getBusinessUnitReference($businessUnitData['main business unit uid']);
            $businessUnit = new BusinessUnit();
            $businessUnitData['owner'] = $mainBusinessUnit;
            $businessUnitData['organization'] = $mainBusinessUnit->getOrganization();

            $uid = $businessUnitData['uid'];
            unset($businessUnitData['uid'], $businessUnitData['main business unit uid']);

            $this->setObjectValues($businessUnit, $businessUnitData);

            $manager->persist($businessUnit);
            $this->addReference('BusinessUnit:' . $uid, $businessUnit);
        }
        $manager->flush();
    }

    /**
     * @param $uid
     * @return BusinessUnit
     * @throws EntityNotFoundException
     */
    public function getBusinessUnitReference($uid)
    {
        $reference = 'BusinessUnit:' . $uid;
        return $this->getReferenceByName($reference);
    }
}
