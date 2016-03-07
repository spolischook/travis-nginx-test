<?php

namespace OroB2B\Bundle\WarehouseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\WarehouseBundle\Model\ExtendWarehouseInventoryLevel;

/**
 * @ORM\Table(
 *     name="orob2b_warehouse_inventory_lev",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="uidx_orob2b_wh_wh_inventory_lev",
 *              columns={"warehouse_id", "product_unit_precision_id"}
 *          )
 *      }
 * )
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class WarehouseInventoryLevel extends ExtendWarehouseInventoryLevel
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var float
     *
     * @ORM\Column(name="quantity", type="decimal", precision=20, scale=10, nullable=false))
     */
    protected $quantity = 0;

    /**
     * @var Warehouse $warehouse
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\WarehouseBundle\Entity\Warehouse")
     * @ORM\JoinColumn(name="warehouse_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $warehouse;

    /**
     * @var Product $product
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $product;

    /**
     * @var ProductUnitPrecision $productUnitPrecision
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision")
     * @ORM\JoinColumn(name="product_unit_precision_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $productUnitPrecision;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return float
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param float $quantity
     * @return WarehouseInventoryLevel
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @return Warehouse
     */
    public function getWarehouse()
    {
        return $this->warehouse;
    }

    /**
     * @param Warehouse $warehouse
     * @return WarehouseInventoryLevel
     */
    public function setWarehouse(Warehouse $warehouse)
    {
        $this->warehouse = $warehouse;

        return $this;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @return ProductUnitPrecision
     */
    public function getProductUnitPrecision()
    {
        return $this->productUnitPrecision;
    }

    /**
     * @param ProductUnitPrecision $productUnitPrecision
     * @return WarehouseInventoryLevel
     */
    public function setProductUnitPrecision(ProductUnitPrecision $productUnitPrecision)
    {
        $this->productUnitPrecision = $productUnitPrecision;
        $this->product = $productUnitPrecision->getProduct();

        return $this;
    }
}
