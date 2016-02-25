<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="orob2b_plist_curr_combined")
 * @ORM\Entity()
 */
class CombinedPriceListCurrency extends BasePriceListCurrency
{
    /**
     * @var CombinedPriceList
     *
     * @ORM\ManyToOne(
     *      targetEntity="OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList",
     *      inversedBy="currencies",
     *      cascade={"persist"}
     * )
     * @ORM\JoinColumn(name="combined_price_list_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $priceList;
}
