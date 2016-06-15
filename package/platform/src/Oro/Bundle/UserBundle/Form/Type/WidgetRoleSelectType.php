<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class WidgetRoleSelectType extends AbstractType
{
    const NAME = 'oro_type_widget_role_select';

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'roles',
                'configs'            => [
                    'multiple'    => true,
                    'width'       => '400px',
                    'placeholder' => 'oro.user.form.choose_role',
                    'allowClear'  => true,
                ]
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_widget_entity_jqueryselect2_hidden';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
