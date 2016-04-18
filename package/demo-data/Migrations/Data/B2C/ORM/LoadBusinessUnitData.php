<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

use OroCRMPro\Bundle\DemoDataBundle\Exception\EntityNotFoundException;

class LoadBusinessUnitData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'user uid',
                'organization uid',
                'main business unit uid',
            ]
        );
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            'main'     => $this->loadData('business_units/main_business_units.csv'),
            'children' => $this->loadData('business_units/children_business_units.csv'),
        ];
    }

    /**
     * Update and return Main Business Unit
     *
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
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        $main = true;
        foreach ($data['main'] as $mainBusinessUnitData) {
            if ($main) {
                $businessUnit = $this->getMainBusinessUnit();
                $main         = false;
            } else {
                $businessUnit = new BusinessUnit();
            }

            $organization = $this->getOrganizationReference($mainBusinessUnitData['organization uid']);
            $businessUnit->setOrganization($organization);
            $this->setObjectValues($businessUnit, $mainBusinessUnitData);

            $this->setBusinessUnitReference($mainBusinessUnitData['uid'], $businessUnit);
            $manager->persist($businessUnit);
        }

        foreach ($data['children'] as $businessUnitData) {
            $mainBusinessUnit = $this->getBusinessUnitReference($businessUnitData['main business unit uid']);
            $businessUnit     = new BusinessUnit();

            $businessUnitData['owner']        = $mainBusinessUnit;
            $businessUnitData['organization'] = $mainBusinessUnit->getOrganization();

            $this->setObjectValues($businessUnit, $businessUnitData);

            $manager->persist($businessUnit);
            $this->setBusinessUnitReference($businessUnitData['uid'], $businessUnit);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 2;
    }
}
