<?php

namespace OroB2B\Bundle\OrderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class OrderAddress extends Constraint implements ConstraintByValidationGroups
{
    /**
     * @var array
     */
    protected $validationGroups;

    /**
     * @return string
     */
    public function validatedBy()
    {
        return 'orob2b_order_address_validator';
    }

    /**
     * @return array
     */
    public function getValidationGroups()
    {
        return $this->validationGroups;
    }
}
