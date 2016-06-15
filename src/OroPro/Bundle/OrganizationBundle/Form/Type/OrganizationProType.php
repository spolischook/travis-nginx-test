<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\OrganizationBundle\Form\Type\OrganizationType;

class OrganizationProType extends OrganizationType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add(
            'appendUsers',
            'oro_entity_identifier',
            [
                'class'    => 'OroUserBundle:User',
                'required' => false,
                'mapped'   => false,
                'multiple' => true
            ]
        )
        ->add(
            'removeUsers',
            'oro_entity_identifier',
            [
                'class'    => 'OroUserBundle:User',
                'required' => false,
                'mapped'   => false,
                'multiple' => true
            ]
        );
    }
}
