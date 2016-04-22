<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;

class ShippingOriginType extends AbstractType
{
    const NAME = 'orob2b_shipping_origin';

    /** @var AddressCountryAndRegionSubscriber */
    private $countryAndRegionSubscriber;

    /** @var string */
    private $dataClass;

    /**
     * @param AddressCountryAndRegionSubscriber $eventListener
     */
    public function __construct(AddressCountryAndRegionSubscriber $eventListener)
    {
        $this->countryAndRegionSubscriber = $eventListener;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'country',
                'oro_country',
                [
                    'label' => 'orob2b.shipping.shipping_origin.country.label',
                    'configs' => ['allowClear' => false, 'placeholder' => 'oro.address.form.choose_country']
                ]
            )
            ->add(
                'region',
                'oro_region',
                [
                    'label' => 'orob2b.shipping.shipping_origin.region.label',
                    'configs' => ['allowClear' => false, 'placeholder' => 'oro.address.form.choose_region']
                ]
            )
            ->add(
                'postalCode',
                'text',
                [
                    'label' => 'orob2b.shipping.shipping_origin.postal_code.label',
                    'attr' => ['placeholder' => 'orob2b.shipping.shipping_origin.postal_code.label']
                ]
            )
            ->add(
                'city',
                'text',
                [
                    'label' => 'orob2b.shipping.shipping_origin.city.label',
                    'attr' => ['placeholder' => 'orob2b.shipping.shipping_origin.city.label']
                ]
            )
            ->add(
                'street',
                'text',
                [
                    'label' => 'orob2b.shipping.shipping_origin.street.label',
                    'attr' => ['placeholder' => 'orob2b.shipping.shipping_origin.street.label']
                ]
            )
            ->add(
                'street2',
                'text',
                [
                    'required' => false,
                    'label' => 'orob2b.shipping.shipping_origin.street2.label',
                    'attr' => ['placeholder' => 'orob2b.shipping.shipping_origin.street2.label']
                ]
            )
            ->add(
                'region_text',
                'hidden',
                [
                    'required' => false,
                    'random_id' => true,
                    'label' => 'orob2b.shipping.shipping_origin.region_text.label',
                    'attr' => ['placeholder' => 'orob2b.shipping.shipping_origin.region_text.label']
                ]
            )->addEventSubscriber($this->countryAndRegionSubscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'intention' => 'shipping_origin'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $parent = $form->getParent();
        if (!$parent) {
            return;
        }

        if (!$parent->has('use_parent_scope_value')) {
            return;
        }
        
        $useParentScopeValue = $parent->get('use_parent_scope_value')->getData();
        foreach ($view->children as $child) {
            $child->vars['use_parent_scope_value'] = $useParentScopeValue;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
