<?php

namespace OroCRM\Bundle\MagentoBundle\Form\Type;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;

class CartItemCollectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setNormalizers(
            [
                'options' => function (Options $options, $options) {
                    if (!$options) {
                        $options = [];
                    }
                    $options['single_form'] = false;
                    return $options;
                }
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_collection';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'orocrm_cart_item_collection';
    }
}
