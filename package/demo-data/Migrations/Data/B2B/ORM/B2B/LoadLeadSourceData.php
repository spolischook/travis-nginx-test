<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2B\ORM\B2B;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadLeadSourceData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @return array
     */
    public function getData()
    {
        return [
            'sources' => $this->loadData('b2b/sources.csv'),
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $data      = $this->getData();
        $className = ExtendHelper::buildEnumValueClassName('lead_source');
        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $manager->getRepository($className);
        $priority = 1;
        foreach ($data['sources'] as $sourceData) {
            $sourceOption = $enumRepo->createEnumValue($sourceData['name'], $priority++, $sourceData['is default']);
            $manager->persist($sourceOption);
            $this->setLeadSourceReference($sourceData['uid'], $sourceOption);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 51;
    }
}
