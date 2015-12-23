<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Visibility;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountAwareInterface;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

/**
 * @ORM\Entity(
 *      repositoryClass="OroB2B\Bundle\AccountBundle\Entity\Visibility\Repository\AccountProductVisibilityRepository"
 * )
 * @ORM\Table(
 *      name="orob2b_acc_product_visibility",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="orob2b_acc_prod_vis_uidx",
 *              columns={"website_id", "product_id", "account_id"}
 *          )
 *      }
 * )
 * @Config
 */
class AccountProductVisibility implements VisibilityInterface, AccountAwareInterface, WebsiteAwareInterface
{
    const ACCOUNT_GROUP = 'account_group';
    const CURRENT_PRODUCT = 'current_product';
    const CATEGORY = 'category';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $product;

    /**
     * @var Website
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\WebsiteBundle\Entity\Website")
     * @ORM\JoinColumn(name="website_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $website;

    /**
     * @var Account
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $account;

    /**
     * @var string
     *
     * @ORM\Column(name="visibility", type="string", length=255, nullable=true)
     */
    protected $visibility;

    public function __clone()
    {
        $this->id = null;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     *
     * @return $this
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * {@inheritdoc}
     */
    public function setAccount(Account $account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * @param Product $product
     * @return string
     */
    public static function getDefault($product)
    {
        return self::ACCOUNT_GROUP;
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @param Product $product
     * @return array
     */
    public static function getVisibilityList($product)
    {
        return [
            self::ACCOUNT_GROUP,
            self::CURRENT_PRODUCT,
            self::CATEGORY,
            self::HIDDEN,
            self::VISIBLE,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetEntity()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     * @return $this
     */
    public function setTargetEntity($product)
    {
        $this->setProduct($product);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * {@inheritdoc}
     */
    public function setWebsite(Website $website)
    {
        $this->website = $website;

        return $this;
    }
}
