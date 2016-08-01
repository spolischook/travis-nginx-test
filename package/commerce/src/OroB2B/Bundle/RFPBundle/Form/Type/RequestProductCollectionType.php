<?php

namespace OroB2B\Bundle\RFPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

class RequestProductCollectionType extends AbstractType
{
    const NAME = 'orob2b_rfp_request_product_collection';

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
            'type' => RequestProductType::NAME,
            'show_form_when_empty'  => true,
            'error_bubbling'        => false,
            'prototype_name'        => '__namerequestproduct__',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
