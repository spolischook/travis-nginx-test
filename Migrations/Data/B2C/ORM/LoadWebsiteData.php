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
            'websites' => $this->loadData('websites.csv'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['websites'] as $websiteData) {
            $website = new Website();
            $this->setObjectValues($website, $websiteData);
            $manager->persist($website);

            $this->setWebsiteReference($websiteData['uid'], $website);
        }
        $manager->flush();
    }
}
