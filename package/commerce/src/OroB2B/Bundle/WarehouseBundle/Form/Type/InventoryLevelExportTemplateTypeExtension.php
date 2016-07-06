<?php

namespace OroB2B\Bundle\WarehouseBundle\Form\Type;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\ImportExportBundle\Form\Type\ExportTemplateType;

use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class InventoryLevelExportTemplateTypeExtension extends AbstractTypeExtension
{
    const NAME = 'orob2b_inventory_level_export_template_type_extension';

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
    public function getExtendedType()
    {
        return ExportTemplateType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!(isset($options['entityName']) && $options['entityName'] == WarehouseInventoryLevel::class)) {
            return;
        }

        $processorAliases = $this->getProcessorAliases();

        $defaultChoice = reset($processorAliases);
        $builder->remove(ExportTemplateType::CHILD_PROCESSOR_ALIAS);

        $builder->add(
            'detailLevel',
            'choice',
            [
                'label' => 'orob2b.warehouse.export.popup.options.label',
                'choices' => $processorAliases,
                'choices_as_values' => false,
                'choice_translation_domain' => true,
                'mapped' => false,
                'multiple' => false,
                'expanded' => true,
                'constraints' => [
                    new NotBlank()
                ],
                'data' => $defaultChoice,
                'required' => true
            ]
        );

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $processorAlias = $event->getForm()->get('detailLevel')->getData();
            $event->getForm()->getData()->setProcessorAlias($processorAlias);
        });
    }

    protected function getProcessorAliases()
    {
        return [
            'orob2b_product.inventory_status_only_export_template' => 'orob2b.warehouse.export.inventory_status_only',
            'orob2b_warehouse.inventory_level_export_template' => 'orob2b.warehouse.export.detailed_inventory_levels',
        ];
    }
}
