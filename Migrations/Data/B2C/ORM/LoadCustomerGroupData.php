<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\MagentoBundle\Entity\CustomerGroup;

class LoadCustomerGroupData extends AbstractFixture
{
    /**
     * @return array
     */
    public function getData()
    {
        return [
            'customer_groups' => $this->loadData('customer_groups.csv')
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['customer_groups'] as $groupData) {
            $uid = $groupData['uid'];
            unset($groupData['uid']);
            $group = new CustomerGroup();
            $this->setObjectValues($group, $groupData);
            $manager->persist($group);

            $this->setReference('CustomerGroup:' . $uid, $group);
        }
        $manager->flush();
    }
}
