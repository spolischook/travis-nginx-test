<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

/**
 * This form type used in system access mode to display record's organization on create/update form
 *
 * Class OrganizationLabelType
 * @package OroPro\Bundle\OrganizationBundle\Form\Type
 */
class OrganizationLabelType extends AbstractType
{
    const NAME = 'oropro_organization_label';

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'entity';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
