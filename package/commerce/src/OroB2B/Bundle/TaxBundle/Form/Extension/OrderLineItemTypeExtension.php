<?php

namespace OroB2B\Bundle\TaxBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use OroB2B\Bundle\TaxBundle\Provider\TaxSubtotalProvider;
use OroB2B\Bundle\OrderBundle\Form\Section\SectionProvider;

class OrderLineItemTypeExtension extends AbstractTypeExtension
{
    const BASE_ORDER = 50;

    /** @var TaxationSettingsProvider */
    protected $taxationSettingsProvider;

    /** @var TaxManager */
    protected $taxManager;

    /** @var TaxSubtotalProvider */
    protected $taxSubtotalProvider;

    /** @var SectionProvider */
    protected $sectionProvider;

    /** @var string */
    protected $extendedType;

    /**
     * @param TaxationSettingsProvider $taxationSettingsProvider
     * @param TaxManager $taxManager
     * @param TaxSubtotalProvider $taxSubtotalProvider
     * @param SectionProvider $sectionProvider
     * @param string $extendedType
     */
    public function __construct(
        TaxationSettingsProvider $taxationSettingsProvider,
        TaxManager $taxManager,
        TaxSubtotalProvider $taxSubtotalProvider,
        SectionProvider $sectionProvider,
        $extendedType
    ) {
        $this->taxationSettingsProvider = $taxationSettingsProvider;
        $this->taxManager = $taxManager;
        $this->taxSubtotalProvider = $taxSubtotalProvider;
        $this->sectionProvider = $sectionProvider;
        $this->extendedType = (string)$extendedType;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return $this->extendedType;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!$this->taxationSettingsProvider->isEnabled()) {
            return;
        }

        $this->taxSubtotalProvider->setEditMode(true);
    }

    /** {@inheritdoc} */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (!$this->taxationSettingsProvider->isEnabled()) {
            return;
        }

        $sections = [];
        $sectionNames = [
            'unitPriceIncludingTax' => 'orob2b.tax.order_line_item.unitPrice.includingTax.label',
            'unitPriceExcludingTax' => 'orob2b.tax.order_line_item.unitPrice.excludingTax.label',
            'unitPriceTaxAmount' => 'orob2b.tax.order_line_item.unitPrice.taxAmount.label',
            'rowTotalIncludingTax' => 'orob2b.tax.order_line_item.rowTotal.includingTax.label',
            'rowTotalExcludingTax' => 'orob2b.tax.order_line_item.rowTotal.excludingTax.label',
            'rowTotalTaxAmount' => 'orob2b.tax.order_line_item.rowTotal.taxAmount.label',
            'taxes' => 'orob2b.tax.order_line_item.taxes.label',
        ];

        $order = self::BASE_ORDER;
        foreach ($sectionNames as $sectionName => $label) {
            $sections[$sectionName] = [
                'order' => $order++,
                'label' => $label,
            ];
        }

        $this->sectionProvider->addSections($this->getExtendedType(), $sections);
    }

    /** {@inheritdoc} */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (!$this->taxationSettingsProvider->isEnabled()) {
            return;
        }

        $entity = $form->getData();
        if (!$entity) {
            return;
        }

        $view->vars['result'] = $this->taxManager->getTax($entity);
    }
}
