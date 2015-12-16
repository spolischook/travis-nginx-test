<?php

namespace OroB2B\Bundle\AccountBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class VisibilityChangeSet extends Constraint
{
    /** @var string */
    public $invalidDataMessage ='orob2b.account.category.visibility.message.invalid_data';

    /** @var string */
    public $entityClass;

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'orob2b.account.catalog.visibility.change_set.validatior';
    }
}
