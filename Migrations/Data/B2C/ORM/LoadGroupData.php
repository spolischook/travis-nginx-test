<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Entity\Group;

class LoadGroupData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\\LoadBusinessUnitData',
        ];
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            'groups' => $this->loadData('groups.csv'),
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
            $this->setGroupReference($groupData['uid'], $group);
        }
        $manager->flush();
    }
}
