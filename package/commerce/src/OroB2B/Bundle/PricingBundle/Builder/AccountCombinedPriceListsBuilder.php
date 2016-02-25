<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @method PriceListToAccountRepository getPriceListToEntityRepository()
 */
class AccountCombinedPriceListsBuilder extends AbstractCombinedPriceListBuilder
{
    /**
     * @param Website $website
     * @param Account $account
     * @param boolean|false $force
     */
    public function build(Website $website, Account $account, $force = false)
    {
        if ($force || !$this->isBuiltForAccount($website, $account)) {
            $this->updatePriceListsOnCurrentLevel($website, $account, $force);
            $this->garbageCollector->cleanCombinedPriceLists();
            $this->setBuiltForAccount($website, $account);
        }
    }

    /**
     * @param Website $website
     * @param AccountGroup $accountGroup
     * @param boolean|false $force
     */
    public function buildByAccountGroup(Website $website, AccountGroup $accountGroup, $force = false)
    {
        if ($force || !$this->isBuiltForAccountGroup($website, $accountGroup)) {
            $accounts = $this->getPriceListToEntityRepository()
                ->getAccountIteratorByDefaultFallback($accountGroup, $website, PriceListAccountFallback::ACCOUNT_GROUP);

            foreach ($accounts as $account) {
                $this->updatePriceListsOnCurrentLevel($website, $account, $force);
            }
            $this->setBuiltForAccountGroup($website, $accountGroup);
        }
    }

    /**
     * @param Website $website
     * @param Account $account
     * @param boolean $force
     */
    protected function updatePriceListsOnCurrentLevel(Website $website, Account $account, $force)
    {
        $priceListsToAccount = $this->getPriceListToEntityRepository()
            ->findOneBy(['website' => $website, 'account' => $account]);
        if (!$priceListsToAccount) {
            /** @var PriceListToAccountRepository $repo */
            $repo = $this->getCombinedPriceListToEntityRepository();
            $repo->delete($account, $website);

            return;
        }
        $collection = $this->priceListCollectionProvider->getPriceListsByAccount($account, $website);
        $combinedPriceList = $this->combinedPriceListProvider->getCombinedPriceList($collection, $force);

        $this->getCombinedPriceListRepository()
            ->updateCombinedPriceListConnection($combinedPriceList, $website, $account);
    }

    /**
     * @param Website $website
     * @param Account $account
     * @return bool
     */
    protected function isBuiltForAccount(Website $website, Account $account)
    {
        return !empty($this->builtList['account'][$website->getId()][$account->getId()]);
    }
    /**
     * @param Website $website
     * @param Account $account
     */
    protected function setBuiltForAccount(Website $website, Account $account)
    {
        $this->builtList['account'][$website->getId()][$account->getId()] = true;
    }

    /**
     * @param Website $website
     * @param AccountGroup $accountGroup
     * @return bool
     */
    protected function isBuiltForAccountGroup(Website $website, AccountGroup $accountGroup)
    {
        return !empty($this->builtList['group'][$website->getId()][$accountGroup->getId()]);
    }
    /**
     * @param Website $website
     * @param AccountGroup $accountGroup
     */
    protected function setBuiltForAccountGroup(Website $website, AccountGroup $accountGroup)
    {
        $this->builtList['group'][$website->getId()][$accountGroup->getId()] = true;
    }
}
