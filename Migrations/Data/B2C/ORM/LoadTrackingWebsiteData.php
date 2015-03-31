<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\TrackingBundle\Entity\TrackingWebsite;

class LoadTrackingWebsiteData extends AbstractFixture implements DependentFixtureInterface
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
                'organization uid'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\\LoadOrganizationData',
            __NAMESPACE__ . '\\LoadDefaultUserData',
        ];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'websites' => $this->loadData('marketing/tracking_websites.csv'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['websites'] as $websiteData) {
            $website = new TrackingWebsite();
            $this->setObjectValues($website, $websiteData);
            $website->setOrganization($this->getOrganizationReference($websiteData['organization uid']));
            $website->setOwner($this->getUserReference($websiteData['user uid']));
            $manager->persist($website);

            $this->setTrackingWebsiteReference($websiteData['uid'], $website);
        }
        $manager->flush();
    }
}
