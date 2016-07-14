<?php

namespace Oro\Bundle\PricingProBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite;
use OroB2B\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadBasePriceListRelationDemoData;
use OroB2B\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadPriceListDemoData;

use Oro\Bundle\WebsiteProBundle\Migrations\Data\Demo\ORM\LoadWebsiteDemoData;

class LoadPriceListToWebsiteDemoData extends LoadBasePriceListRelationDemoData
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator
            ->locate('@OroPricingProBundle/Migrations/Data/Demo/ORM/data/price_lists_to_website.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));
            /** @var EntityManager $manager */
            $priceList = $this->getPriceListByName($manager, $row['priceList']);
            $website = $this->getWebsiteByName($manager, $row['website']);

            $priceListToAccount = new PriceListToWebsite();
            $priceListToAccount->setWebsite($website)
                ->setPriceList($priceList)
                ->setPriority($row['priority'])
                ->setMergeAllowed((boolean)$row['mergeAllowed']);
            $manager->persist($priceListToAccount);
        }

        fclose($handler);

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadWebsiteDemoData::class, LoadPriceListDemoData::class];
    }
}
