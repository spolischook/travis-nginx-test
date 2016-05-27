<?php

namespace Oro\Bundle\LocaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CurrencyType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'choices' => Intl::getCurrencyBundle()->getCurrencyNames('en'),
                'restrict' => false
            )
        );

        $resolver->setDefined('restrict');
        $resolver->setAllowedTypes('restrict', 'bool');
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'currency';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_currency';
    }
}
