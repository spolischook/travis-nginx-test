<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

class ProductUnitHolderTypeStub extends AbstractType
{
    const NAME = 'orob2b_stub_product_unit_holder';

    /**
     * @var string
     */
    protected $formTypeName;

    /**
     * ProductUnitHolderTypeStub constructor.
     * @param string $formTypeName
     */
    public function __construct($formTypeName)
    {
        $this->formTypeName = $formTypeName;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('productUnit', $this->formTypeName, [
                'label' =>  'orob2b.productunit.entity_label',
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => 'OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface',
            'create_enabled' => true,
            'configs' => [
                'placeholder' => null,
            ],
        ]);
    }
}
