<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Type;

use Doctrine\ORM\PersistentCollection;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Form\Type\OrganizationsSelectType;

use OroPro\Bundle\OrganizationBundle\Helper\OrganizationProHelper;

class OrganizationsProSelectType extends OrganizationsSelectType
{
    /** @var OrganizationProHelper */
    protected $organizationProHelper;

    /** @var array */
    protected $businessUnitsTree;

    /**
     * @param OrganizationProHelper $organizationProHelper
     */
    public function setOrganizationProHelper(OrganizationProHelper $organizationProHelper)
    {
        $this->organizationProHelper = $organizationProHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['show_organizations_selector'] = !$this->organizationProHelper->isGlobalOrganizationExists() ||
            $this->securityFacade->getOrganization()->getIsGlobal();

        $buTree = $this->getFormBusinessUnitsTree();
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
        $view->vars['accordion_enabled'] = $this->buManager->getTreeNodesCount($buTree) > 1000;
    }

    /**
     * @return array
     */
    protected function getFormBusinessUnitsTree()
    {
        return array_intersect_key(
            $this->getBusinessUnitsTree(),
            array_flip($this->getOrganizationOptionsIds())
        );
    }

    /**
     * @return array
     */
    protected function getBusinessUnitsTree()
    {
        if ($this->businessUnitsTree === null) {
            $this->businessUnitsTree = $this->buManager->getBusinessUnitRepo()->getOrganizationBusinessUnitsTree(
                null,
                ['is_global' => 'DESC']
            );
        }

        return $this->businessUnitsTree;
    }

    /**
     * @return int[]
     */
    protected function getOrganizationOptionsIds()
    {
        $ids = [];
        $organizations = $this->getAvailableOrganizations();
        foreach ($organizations as $organization) {
            $ids[] = $organization->getId();
        }

        return $ids;
    }

    /**
     * Return organizations can be edited by current user
     *
     * @return Organization[]
     */
    protected function getAvailableOrganizations()
    {
        if ($this->securityFacade->getOrganization()->getIsGlobal()) {
            return $this->em->getRepository('OroOrganizationBundle:Organization')->findAll();
        }

        if ($this->organizationProHelper->isGlobalOrganizationExists()) {
            return [$this->securityFacade->getOrganization()];
        }

        return $this->getLoggedInUser()->getOrganizations(false);
    }
}
