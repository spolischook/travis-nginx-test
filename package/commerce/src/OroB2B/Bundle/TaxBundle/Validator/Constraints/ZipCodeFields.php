<?php

namespace OroB2B\Bundle\TaxBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ZipCodeFields extends Constraint
{
    /**
     * @var string
     */
    public $onlyOneTypeMessage = 'orob2b.tax.validator.constraints.single_or_range';

    /**
     * @var string
     */
    public $rangeShouldHaveBothFieldMessage = 'orob2b.tax.validator.constraints.range_start_and_end_required';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return ZipCodeFieldsValidator::ALIAS;
    }
}
