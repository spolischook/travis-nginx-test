<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\MagentoBundle\Entity\Website;

class LoadWebsiteData extends AbstractFixture
{
    /**
     * @return array
     */
    public function getData()
    {
        return [
            'websites' => $this->loadData('websites.csv')
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['websites'] as $websiteData) {
            $uid = $websiteData['uid'];
            unset($websiteData['uid']);
            $website = new Website();
            $this->setObjectValues($website, $websiteData);
            $manager->persist($website);

            $this->setReference('Website:' . $uid, $website);
        }
        $manager->flush();
    }
}
