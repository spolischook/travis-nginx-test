<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;

class LoadMarketingSegmentData extends AbstractFixture implements DependentFixtureInterface
{
    /** @var EntityRepository */
    protected $segmentTypeRepository;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->segmentTypeRepository = $this->em->getRepository('OroSegmentBundle:SegmentType');
    }

    /**
     * @return array
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'type',
                'business unit uid',
                'marketing list uid'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\\LoadMarketingListData',
        ];
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            'segments' => $this->loadData('marketing/segments.csv'),
            'columns' => $this->loadData('marketing/segments_definition_columns.csv'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['segments'] as $segmentData) {
            $list = $this->getMarketingListReference($segmentData['marketing list uid']);

            $segment = new Segment();
            $segment->setEntity($list->getEntity());
            $segment->setName('Marketing List ' . $list->getName() . ' segment');
            $segment->setDefinition($segmentData['description']);

            $this->addDefinition($segment, $segmentData['uid']);
            $segment->setOwner($this->getBusinessUnitReference($segmentData['business unit uid']));
            $segment->setOrganization($list->getOrganization());
            $segment->setType($this->getSegmentType($segmentData['type']));
            $list->setSegment($segment);
            $manager->persist($list);
        }
        $manager->flush();
    }

    /**
     * @param Segment $segment
     * @param $uid
     */
    protected function addDefinition(Segment $segment, $uid)
    {
        $data = $this->getData();

        $columns = array_filter(
            $data['columns'],
            function ($columnData) use ($uid) {
                return $columnData['segment uid'] == $uid;
            }
        );

        $definition = [
            'columns' => []
        ];

        foreach ($columns as $column) {
            $definition['columns'][] = [
                'name' => $column['name'],
                'label' => $column['label'],
                'sorting' => '',
                'func' => null,
            ];
        }
        $segment->setDefinition(json_encode($definition));
    }

    /**
     * @param $name
     * @return SegmentType
     * @throws EntityNotFoundException
     */
    protected function getSegmentType($name)
    {
        /** @var SegmentType $type */
        $type = $this->segmentTypeRepository->findOneBy(['name' => $name]);
        if (!$type) {
            throw new EntityNotFoundException('Segment type ' . $name . ' not found!');
        }
        return $type;
    }
}
