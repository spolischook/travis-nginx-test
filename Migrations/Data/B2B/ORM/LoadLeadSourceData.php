<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2B\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadLeadSourceData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $data      = $this->loadData('leads/sources.csv');
        $className = ExtendHelper::buildEnumValueClassName('lead_source');
        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $manager->getRepository($className);
        $priority = 1;
        foreach ($data as $sourceData) {
            $sourceOption = $enumRepo->createEnumValue($sourceData['name'], $priority++, $sourceData['is default']);
            $manager->persist($sourceOption);
            $this->setLeadSourceReference($sourceData['uid'], $sourceOption);
        }

        $manager->flush();
    }
}
