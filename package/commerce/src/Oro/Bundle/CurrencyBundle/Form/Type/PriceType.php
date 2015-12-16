<?php

namespace Oro\Bundle\CurrencyBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\DataTransformer\PriceTransformer;

class PriceType extends AbstractType
{
    const NAME = 'oro_currency_price';

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
        if (empty($options['hide_currency'])) {
            $currencyType = CurrencySelectionType::NAME;
            $currencyOptions = [
                'additional_currencies' => $options['additional_currencies'],
                'currencies_list' => $options['currencies_list'],
                'full_currency_list' => $options['full_currency_list'],
                'compact' => $options['compact'],
                'required' => true,
                'empty_value' => $options['currency_empty_value'],
            ];
        } else {
            $currencyType = 'hidden';
            $currencyOptions = [
                'data' => $options['default_currency']
            ];
        }

        $builder
            ->add('value', 'number', ['required' => true])
            ->add('currency', $currencyType, $currencyOptions);

        $builder->addViewTransformer(new PriceTransformer());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'hide_currency' => false,
            'additional_currencies' => null,
            'cascade_validation' => true,
            'currencies_list' => null,
            'default_currency' => null,
            'full_currency_list' => false,
            'currency_empty_value' => 'oro.currency.currency.form.choose',
            'compact' => false
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['hide_currency'] = $options['hide_currency'];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
