<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

class ProductShippingOptionsCollectionType extends AbstractType
{
    const NAME = 'orob2b_shipping_product_shipping_options_collection';

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'type' => ProductShippingOptionsType::NAME,
            'show_form_when_empty' => true,
            'error_bubbling' => false,
            'add_label' => 'orob2b.shipping.product_shipping_options.add_label',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
