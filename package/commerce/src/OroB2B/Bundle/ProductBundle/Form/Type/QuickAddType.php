<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class QuickAddType extends AbstractType
{
    const NAME = 'orob2b_product_quick_add';

    const PRODUCTS_FIELD_NAME = 'products';
    const COMPONENT_FIELD_NAME = 'component';
    const ADDITIONAL_FIELD_NAME = 'additional';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                self::PRODUCTS_FIELD_NAME,
                ProductRowCollectionType::NAME,
                [
                    'required' => false,
                    'options' => [
                        'validation_required' => $options['validation_required']
                    ],
                    'error_bubbling' => true,
                    'constraints' => [new NotBlank(['message' => 'orob2b.product.at_least_one_item'])],
                    'add_label' => 'orob2b.product.form.add_row',
                    'products' => $options['products'],
                ]
            )
            ->add(
                self::COMPONENT_FIELD_NAME,
                'hidden'
            )
            ->add(
                self::ADDITIONAL_FIELD_NAME,
                'hidden'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'validation_required' => false,
                'products' => null,
            ]
        );
        $resolver->setAllowedTypes('validation_required', 'bool');
        $resolver->setAllowedTypes('products', ['array', 'null']);
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
