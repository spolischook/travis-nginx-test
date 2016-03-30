<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Tracking;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\TrackingBundle\Entity\TrackingWebsite;
use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadTrackingWebsiteData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'user uid',
                'organization uid',
                'channel uid',
            ]
        );
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'websites' => $this->loadData('tracking/tracking_websites.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $manager->getClassMetadata('Oro\Bundle\TrackingBundle\Entity\TrackingWebsite')->setLifecycleCallbacks([]);

        $data = $this->getData();

        foreach ($data['websites'] as $websiteData) {
            $website = new TrackingWebsite();
            $this->setObjectValues($website, $websiteData);
            $website->setOrganization($this->getOrganizationReference($websiteData['organization uid']));
            $website->setOwner($this->getUserReference($websiteData['user uid']));

            $website->setChannel($this->getChannelReference($websiteData['channel uid']));
            $website->setCreatedAt($this->generateCreatedDate());
            $website->setUpdatedAt($website->getCreatedAt());
            $manager->persist($website);

            $this->setTrackingWebsiteReference($websiteData['uid'], $website);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 40;
    }
}
