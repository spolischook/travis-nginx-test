<?php

namespace OroB2B\Bundle\InvoiceBundle\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\SaleBundle\Formatter\QuoteProductFormatter;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Form\Type\QuantityType;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceTypeSelectorType;
use OroB2B\Bundle\PricingBundle\Rounding\PriceRoundingService;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InvoiceLineItemType extends AbstractType
{
    const NAME = 'orob2b_invoice_line_item';

    /**
     * @var ProductUnitLabelFormatter
     */
    protected $labelFormatter;

    /**
     * @var QuoteProductFormatter
     */
    protected $formatter;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var string
     */
    protected $productUnitClass;

    /**
     * @var PriceRoundingService
     */
    protected $priceRounding;

    /**
     * @param Registry $registry
     * @param ProductUnitLabelFormatter $labelFormatter
     * @param PriceRoundingService $priceRoundingService
     */
    public function __construct(
        Registry $registry,
        ProductUnitLabelFormatter $labelFormatter,
        PriceRoundingService $priceRoundingService
    ) {
        $this->registry = $registry;
        $this->labelFormatter = $labelFormatter;
        $this->priceRounding = $priceRoundingService;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param string $productUnitClass
     */
    public function setProductUnitClass($productUnitClass)
    {
        $this->productUnitClass = $productUnitClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['page_component'] = $options['page_component'];
        $view->vars['page_component_options'] = $options['page_component_options'];
        $view->vars['page_component_options']['currency'] = $options['currency'];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add(
                'product',
                ProductSelectType::NAME,
                [
                    'required' => false,
                    'label' => 'orob2b.product.entity_label',
                    'create_enabled' => false,
                ]
            )
            ->add(
                'productSku',
                'text',
                [
                    'required' => false,
                    'label' => 'orob2b.product.sku.label',
                ]
            )
            ->add(
                'freeFormProduct',
                'text',
                [
                    'required' => false,
                    'label' => 'orob2b.product.entity_label',
                ]
            )
            ->add(
                'quantity',
                QuantityType::NAME,
                [
                    'required' => true,
                    'label' => 'orob2b.order.invoicelineitem.quantity.label',
                    'default_data' => 1,
                    'product_holder' => $builder->getData(),
                ]
            )
            ->add(
                'productUnit',
                ProductUnitSelectionType::NAME,
                [
                    'label' => 'orob2b.product.productunit.entity_label',
                    'required' => true,
                ]
            )
            ->add(
                'price',
                PriceType::NAME,
                [
                    'error_bubbling' => false,
                    'required' => true,
                    'label' => 'orob2b.invoice.invoicelineitem.price.label',
                    'hide_currency' => true,
                    'default_currency' => $options['currency'],
                ]
            )
            ->add('sortOrder', 'hidden')
            ->add(
                'priceType',
                PriceTypeSelectorType::NAME
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['currency']);
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'intention' => 'invoice_line_item',
                'page_component' => 'oroui/js/app/components/view-component',
                'page_component_options' => [
                    'view' => 'orob2binvoice/js/app/views/invoice-line-item-view',
                    'freeFormUnits' => $this->getFreeFormUnits(),
                    'precision' => $this->priceRounding->getPrecision()
                ],
                'currency' => null,
            ]
        );
        $resolver->setAllowedTypes('page_component_options', 'array');
        $resolver->setAllowedTypes('page_component', 'string');
        $resolver->setAllowedTypes('currency', ['null', 'string']);
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

    /**
     * @return array
     */
    protected function getFreeFormUnits()
    {
        $units = $this->registry->getRepository($this->productUnitClass)->findBy([], ['code' => 'ASC']);

        return $this->labelFormatter->formatChoices($units);
    }
}
