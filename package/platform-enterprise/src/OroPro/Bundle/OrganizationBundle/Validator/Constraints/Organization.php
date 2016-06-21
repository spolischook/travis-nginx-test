<?php

namespace OroPro\Bundle\OrganizationBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Organization extends Constraint
{
    public $message = 'You have no access to set this value as {{ organization }}.';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'organization_validator';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
