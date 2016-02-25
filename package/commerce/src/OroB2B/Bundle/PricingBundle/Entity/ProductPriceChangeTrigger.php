<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\B2BEntityBundle\Storage\ObjectIdentifierAwareInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @ORM\Table(
 *      name="orob2b_prod_price_ch_trigger",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="orob2b_changed_product_price_list_unq", columns={
 *              "product_id",
 *              "price_list_id"
 *          })
 *      }
 * )
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceChangeTriggerRepository")
 */
class ProductPriceChangeTrigger implements ObjectIdentifierAwareInterface
{
    /**
     * @var integer $id
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var PriceList
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\PricingBundle\Entity\PriceList")
     * @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $priceList;

    /**
     * @var Product
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $product;

    /**
     * @param PriceList $priceList
     * @param Product $product
     */
    public function __construct(PriceList $priceList, Product $product)
    {
        $this->priceList = $priceList;
        $this->product = $product;
    }

    /**
     * @return PriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectIdentifier()
    {
        if (!$this->product->getId() || !$this->priceList->getId()) {
            throw new \InvalidArgumentException('Product id and priceList id, required for identifier generation');
        }

        return ClassUtils::getClass($this) . '_' . $this->product->getId() . '_' . $this->priceList->getId();
    }
}
