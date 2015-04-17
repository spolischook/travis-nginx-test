<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\MailChimp;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\MailChimpBundle\Entity\StaticSegment;
use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadMailChimpStaticSegmentData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @return array
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'organization uid',
                'mailchimp subscriber list uid',
                'marketing list uid',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadOrganizationData',
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadMarketingListData',
            __NAMESPACE__ . '\\LoadMailChimpSubscriberListData',
        ];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'static_segments' => $this->loadData('mailchimp/static_segments.csv'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['static_segments'] as $segmentData) {
            $segment = new StaticSegment();
            $this->setObjectValues($segment, $segmentData);
            $segment->setOwner($this->getOrganizationReference($segmentData['organization uid']));
            $segment->setSubscribersList(
                $this->getMailChimpSubscriberListReference($segmentData['mailchimp subscriber list uid'])
            );
            $segment->setMarketingList($this->getMarketingListReference($segmentData['marketing list uid']));
            $segment->setRemoteRemove(false);
            $segment->setLastSynced($this->generateUpdatedDate(new \DateTime('now - 1 week')));
            $manager->persist($segment);
        }
        $manager->flush();
    }
}
