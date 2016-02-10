<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * @ORM\Entity(
 *    repositoryClass="OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountCategoryRepository"
 * )
 * @ORM\Table(name="orob2b_acc_ctgr_vsb_resolv")
 */
class AccountCategoryVisibilityResolved extends BaseCategoryVisibilityResolved
{
    /**
     * @var Account
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $account;

    /**
     * @var AccountCategoryVisibility
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility")
     * @ORM\JoinColumn(name="source_category_visibility", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $sourceCategoryVisibility;

    /**
     * @param Category $category
     * @param Account $account
     */
    public function __construct(Category $category, Account $account)
    {
        $this->account = $account;
        parent::__construct($category);
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @return AccountCategoryVisibility
     */
    public function getSourceCategoryVisibility()
    {
        return $this->sourceCategoryVisibility;
    }

    /**
     * @param AccountCategoryVisibility $sourceVisibility
     * @return $this
     */
    public function setSourceCategoryVisibility(AccountCategoryVisibility $sourceVisibility)
    {
        $this->sourceCategoryVisibility = $sourceVisibility;

        return $this;
    }
}
