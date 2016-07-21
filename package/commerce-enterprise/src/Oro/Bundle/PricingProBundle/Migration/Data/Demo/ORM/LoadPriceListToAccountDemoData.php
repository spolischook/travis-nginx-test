<?php

namespace Oro\Bundle\PricingProBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadBasePriceListRelationDemoData;
use Oro\Bundle\WebsiteProBundle\Migrations\Data\Demo\ORM\LoadWebsiteDemoData;

class LoadPriceListToAccountDemoData extends LoadBasePriceListRelationDemoData
{
    /**
     * @var Account[]
     */
    protected $accounts;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $locator = $this->container->get('file_locator');
        $filePath = $locator
            ->locate('@OroPricingProBundle/Migrations/Data/Demo/ORM/data/price_lists_to_account.csv');

        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');
        /** @var EntityManager $manager */
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));
            $account = $this->getAccountByName($manager, $row['account']);
            $priceList = $this->getPriceListByName($manager, $row['priceList']);
            $website = $this->getWebsiteByName($manager, $row['website']);

            $priceListToAccount = new PriceListToAccount();
            $priceListToAccount->setAccount($account)
                ->setPriceList($priceList)
                ->setWebsite($website)
                ->setPriority($row['priority'])
                ->setMergeAllowed((boolean)$row['mergeAllowed']);

            $manager->persist($priceListToAccount);
        }

        fclose($handler);

        $manager->flush();
    }

    /**
     * @param EntityManager $manager
     * @param string $name
     * @return Account
     */
    protected function getAccountByName(EntityManager $manager, $name)
    {
        foreach ($this->getAccounts($manager) as $account) {
            if ($account->getName() === $name) {
                return $account;
            }
        }

        throw new \LogicException(sprintf('There is no account with name "%s" .', $name));
    }

    /**
     * @param EntityManager $manager
     * @return array|Account[]
     */
    protected function getAccounts(EntityManager $manager)
    {
        if (!$this->accounts) {
            $this->accounts = $manager->getRepository('OroB2BAccountBundle:Account')->findAll();
        }

        return $this->accounts;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return array_merge(parent::getDependencies(), [LoadWebsiteDemoData::class]);
    }
}
