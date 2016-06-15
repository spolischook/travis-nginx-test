<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductUnitPrecisionType extends AbstractType
{
    const NAME = 'orob2b_product_unit_precision';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('precision', 'integer', ['type' => 'text', 'required' => false])
            ->add('conversionRate', 'number', ['required' => false])
            ->add('sell', 'checkbox', ['required' => false])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $unitPrecision = $event->getData();
            $form = $event->getForm();

            if ($unitPrecision instanceof ProductUnitPrecision && $unitPrecision->getUnit()) {
                if ($unitPrecision->getId()) {
                    $disabled = true;
                } else {
                    $disabled = false;
                }
                $form->add(
                    'unit_disabled',
                    ProductUnitSelectionType::NAME,
                    [
                       'compact' => $options['compact'],
                       'disabled' => $disabled,
                       'mapped' => false,
                       'data' => $unitPrecision->getUnit()
                    ]
                );
                $form->add('unit', ProductUnitSelectionType::NAME, ['attr' => ['class' => 'hidden-unit']]);
            } else {
                $form->add('unit', ProductUnitSelectionType::NAME, ['compact' => $options['compact']]);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'compact' => false
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
