<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;

use OroCRMPro\Bundle\DemoDataBundle\Exception\EntityNotFoundException;
use Symfony\Component\DependencyInjection\ContainerInterface;

use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;
use OroCRM\Bundle\MarketingListBundle\Entity\MarketingListType;

class LoadMarketingListData extends AbstractFixture implements DependentFixtureInterface
{
    /** @var EntityRepository */
    protected $marketingListTypeRepository;

    /**
     * {@inheritDoc}
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
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\\LoadDefaultUserData',
            __NAMESPACE__ . '\\LoadOrganizationData',
            __NAMESPACE__ . '\\LoadMarketingSegmentData',
        ];
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
     * {@inheritDoc}
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
}
