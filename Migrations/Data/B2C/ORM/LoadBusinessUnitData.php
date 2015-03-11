<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LoadBusinessUnitData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @return array
     */
    protected function getData()
    {
        return
        [
            'main' => current($this->loadData('business_units/main_business_units.csv')),
            'children'  => $this->loadData('business_units/children_business_units.csv'),
        ];
    }

    /**
     * Update and return Main Business Unit
     * @param ObjectManager $manager
     * @param array $data
     * @return BusinessUnit
     * @throws EntityNotFoundException
     */
    protected function getMainBusinessUnit(ObjectManager $manager, $data = [])
    {
        $businessRepository = $this->em->getRepository('OroOrganizationBundle:BusinessUnit');

        /** @var BusinessUnit $mainBusinessUnit */
        $entity = $businessRepository->findOneBy(['name' => 'Main']);
        if (!$entity) {
            $entity = $businessRepository->findOneBy(['name' => 'Acme, General']);
        }

        if (!$entity) {
            throw new EntityNotFoundException('Main business unit is not defined.');
        }

        /**
         * TODO:Move to reset command
         */
        $this->removeOldData('OroOrganizationBundle:BusinessUnit', $entity);

        $this->setObjectValues($entity, $data);
        $manager->persist($entity);
        $this->addReference('OroCRMLiveDemoBundle:mainBusiness', $entity);

        return $entity;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        /** @var Organization $organization */
        $organization = $this->getMainOrganization();

        /** @var BusinessUnit $mainBusinessUnit */
        unset($data['main']['uid']);
        $mainBusinessUnit = $this->getMainBusinessUnit($manager, $data['main']);

        foreach($data['children'] as $businessUnitData)
        {
            /** @var BusinessUnit $oroUnit */
            $businessUnit = new BusinessUnit();

            $businessUnitData['owner'] = $mainBusinessUnit;
            $businessUnitData['organization'] = $organization;

            $uid = $businessUnitData['uid'];
            unset($businessUnitData['uid']);

            $this->setObjectValues($businessUnit, $businessUnitData);

            $manager->persist($businessUnit);
            $this->addReference('OroCRMLiveDemoBundle:businessUnit:' . $uid, $businessUnit);
        }
        $manager->flush();
    }
}
