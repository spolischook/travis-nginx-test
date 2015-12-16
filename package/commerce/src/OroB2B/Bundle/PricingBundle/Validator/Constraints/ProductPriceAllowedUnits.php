<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Import validation
 */
class ProductPriceAllowedUnits extends Constraint
{
    /**
     * @var string
     */
    public $notExistingProductMessage = 'orob2b.pricing.validators.product_price.not_existing_product.message';

    /**
     * @var string
     */
    public $notExistingUnitMessage = 'orob2b.pricing.validators.product_price.not_existing_unit.message';

    /**
     * @var string
     */
    public $notAllowedUnitMessage = 'orob2b.pricing.validators.product_price.not_allowed_unit.message';

    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return 'orob2b_pricing_product_price_allowed_units_validator';
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
