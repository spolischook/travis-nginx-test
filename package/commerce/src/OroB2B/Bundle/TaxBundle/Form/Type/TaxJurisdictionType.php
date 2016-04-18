<?php

namespace OroB2B\Bundle\TaxBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Form\EventListener\AddressCountryAndRegionSubscriber;

class TaxJurisdictionType extends AbstractType
{
    const NAME = 'orob2b_tax_jurisdiction_type';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var AddressCountryAndRegionSubscriber
     */
    protected $countryAndRegionSubscriber;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param AddressCountryAndRegionSubscriber $countryAndRegionSubscriber
     */
    public function __construct(AddressCountryAndRegionSubscriber $countryAndRegionSubscriber)
    {
        $this->countryAndRegionSubscriber = $countryAndRegionSubscriber;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->countryAndRegionSubscriber);

        $builder
            ->add('code', 'text', [
                'label' => 'orob2b.tax.taxjurisdiction.code.label',
                'required' => true
            ])
            ->add('description', 'textarea', [
                'label' => 'orob2b.tax.taxjurisdiction.description.label',
                'required' => false
            ])
            ->add('country', 'oro_country', [
                'required' => true,
                'label' => 'orob2b.tax.taxjurisdiction.country.label'
            ])
            ->add('region', 'oro_region', [
                'required' => false,
                'label' => 'orob2b.tax.taxjurisdiction.region.label'
            ])
            ->add('region_text', 'hidden', [
                'required' => false,
                'random_id' => true,
                'label' => 'orob2b.tax.taxjurisdiction.region_text.label'
            ])
            ->add('zipCodes', ZipCodeCollectionType::NAME, [
                'required' => false,
                'label' => 'orob2b.tax.taxjurisdiction.zip_codes.label',
                'tooltip'  => 'orob2b.tax.form.tooltip.zip_codes'
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
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
