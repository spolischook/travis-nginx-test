<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use OroCRMPro\Bundle\DemoDataBundle\Exception\EntityNotFoundException;

class LoadMarketingSegmentData extends AbstractFixture implements OrderedFixtureInterface
{
    /** @var EntityRepository */
    protected $segmentTypeRepository;

    /**
     * {@inheritdoc}
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
                'organization uid',
                'business unit uid',
            ]
        );
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            'segments' => $this->loadData('marketing/segments.csv'),
            'columns'  => $this->loadData('marketing/segments_definition_columns.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['segments'] as $segmentData) {
            $segment = new Segment();
            $this->setObjectValues($segment, $segmentData);
            $this->addDefinition($segment, $segmentData['uid']);
            $segment->setOrganization($this->getOrganizationReference($segmentData['organization uid']));
            $segment->setOwner($this->getBusinessUnitReference($segmentData['business unit uid']));
            $segment->setType($this->getSegmentType($segmentData['type']));
            $this->setSegmentReference($segmentData['uid'], $segment);
            $manager->persist($segment);
        }
        $manager->flush();
    }

    /**
     * @param Segment $segment
     * @param         $uid
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
                'name'    => $column['name'],
                'label'   => $column['label'],
                'sorting' => '',
                'func'    => null,
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

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 11;
    }
}
