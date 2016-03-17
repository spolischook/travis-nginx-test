<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingListType;

use OroCRMPro\Bundle\DemoDataBundle\Exception\EntityNotFoundException;

class LoadMarketingListData extends AbstractFixture implements OrderedFixtureInterface
{
    /** @var EntityRepository */
    protected $marketingListTypeRepository;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->marketingListTypeRepository = $this->em->getRepository('OroCRMMarketingListBundle:MarketingListType');
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
                'user uid',
                'segment uid',
                'organization uid',
            ]
        );
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            'marketing_lists' => $this->loadData('marketing/lists.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['marketing_lists'] as $listData) {
            $list = new MarketingList();
            $this->setObjectValues($list, $listData);

            $list->setOrganization($this->getOrganizationReference($listData['organization uid']));
            $list->setOwner($this->getUserReference($listData['user uid']));
            $list->setType($this->getMarketingListType($listData['type']));
            $this->setMarketingListReference($listData['uid'], $list);
            $list->setCreatedAt($this->generateCreatedDate());
            $list->setUpdatedAt($list->getCreatedAt());

            $list->setSegment($this->getSegmentReference($listData['segment uid']));
            $manager->getClassMetadata(get_class($list))->setLifecycleCallbacks([]);
            $manager->persist($list);
        }
        $manager->flush();
    }

    /**
     * @param $name
     * @return MarketingListType
     * @throws EntityNotFoundException
     */
    protected function getMarketingListType($name)
    {
        /** @var MarketingListType $type */
        $type = $this->marketingListTypeRepository->findOneBy(['name' => $name]);
        if (!$type) {
            throw new EntityNotFoundException('Marketing list type ' . $name . ' not found!');
        }
        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 12;
    }
}
