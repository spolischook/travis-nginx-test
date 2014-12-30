<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Type;

use Doctrine\ORM\PersistentCollection;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\OrganizationBundle\Form\Type\OrganizationsSelectType;

class OrganizationsProSelectType extends OrganizationsSelectType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'organizations',
            'entity',
            [
                'class'    => 'OroOrganizationBundle:Organization',
                'property' => 'name',
                'multiple' => true,
                'expanded' => true,
                'choices'  => $this->getOrganizationOptions(),
            ]
        );
        $builder->add(
            'businessUnits',
            'oro_business_unit_tree',
            [
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['show_organizations_selector'] = true;

        $buTree = $this->buManager->getBusinessUnitRepo()->getOrganizationBusinessUnitsTree(
            null,
            ['is_global' => 'DESC']
        );

        $view->vars['organization_tree_ids'] = $buTree;

        /** @var PersistentCollection $organizationsData */
        $organizationsData = $view->vars['data']->getOrganizations();
        if ($organizationsData) {
            $organizationsData = $organizationsData->map(
                function ($item) {
                    return $item->getId();
                }
            )->getValues();
        }

        /** @var PersistentCollection $businessUnitData */
        $businessUnitData = $view->vars['data']->getBusinessUnits();
        if ($businessUnitData) {
            $businessUnitData = $businessUnitData->map(
                function ($item) {
                    return $item->getId();
                }
            )->getValues();
        }

        $view->vars['selected_organizations']  = $organizationsData;
        $view->vars['selected_business_units'] = $businessUnitData;
    }
}
