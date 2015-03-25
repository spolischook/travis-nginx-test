<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

class LoadGroupData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadBusinessUnitData'];
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            'groups' => $this->loadData('groups.csv')
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        foreach ($data['groups'] as $groupData) {
            $businessUnit = $this->getBusinessUnitReference($groupData['business unit uid']);
            $group = new Group($groupData['name']);
            $group->setOwner($businessUnit);
            $group->setOrganization($businessUnit->getOrganization());
            $manager->persist($group);
            $this->setReference('Group:' . $groupData['uid'], $group);
        }
        $manager->flush();
    }

    /**
     * @param $uid
     * @return BusinessUnit
     * @throws \Doctrine\ORM\EntityNotFoundException
     */
    protected function getBusinessUnitReference($uid)
    {
        $reference = 'BusinessUnit:' . $uid;
        return $this->getReferenceByName($reference);
    }
}
