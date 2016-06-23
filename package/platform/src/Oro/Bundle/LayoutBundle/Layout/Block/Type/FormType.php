<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockBuilderInterface;

class FormType extends AbstractType
{
    const NAME = 'form';
    const FIELD_SEPARATOR = '_';

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('form_name', 'form');
        $resolver->setDefined([
            'form',
            'form_action',
            'form_route_name',
            'form_route_parameters',
            'form_method',
            'form_enctype',
            'form_data',
            'form_prefix',
            'form_field_prefix',
            'form_group_prefix',
            'render_rest',
            'preferred_fields',
            'groups',
            'split_to_fields',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildBlock(BlockBuilderInterface $builder, array $options)
    {

        $this->addBlockType(
            $builder,
            FormStartType::NAME,
            $options,
            [
                'form',
                'form_name',
                'form_action',
                'form_route_name',
                'form_route_parameters',
                'form_method',
                'form_enctype',
            ]
        );

        $this->addBlockType(
            $builder,
            FormFieldsType::NAME,
            $options,
            [
                'form',
                'form_name',
                'groups',
                'form_prefix',
                'form_field_prefix',
                'form_group_prefix',
                'split_to_fields',
                'form_data',
                'preferred_fields'
            ]
        );

        $this->addBlockType(
            $builder,
            FormEndType::NAME,
            $options,
            [
                'form',
                'form_name',
                'render_rest',
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param BlockBuilderInterface $builder
     * @param string                $name
     * @param array                 $options
     * @param array                 $passedOptions
     */
    protected function addBlockType(BlockBuilderInterface $builder, $name, array $options, array $passedOptions)
    {
        $builder->getLayoutManipulator()->add(
            $builder->getId().self::FIELD_SEPARATOR.$name,
            $builder->getId(),
            $name,
            array_intersect_key($options, array_flip($passedOptions))
        );
    }
}
