<?php

namespace OroPro\Bundle\UserBundle\EventListener\Datagrid;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

use OroPro\Bundle\OrganizationBundle\Helper\OrganizationProHelper;
use OroPro\Bundle\UserBundle\Helper\UserProHelper;

/**
 * Class RoleListener.
 *
 * Modifies grid of roles, adds Organization column, filter and sorter.
 *
 * Filters Roles based on these requirements:
 * - User assigned and logged in to Global Organization is able to view all Roles;
 * - Otherwise User is able to view Roles assigned only to current Organization or Roles without Organization.
 */
class RoleListener
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var UserProHelper
     */
    protected $userHelper;

    /**
     * @var null|Organization
     */
    private $organization = null;

    /**
     * @param UserProHelper $userHelper
     * @param OrganizationProHelper $organizationHelper
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        UserProHelper $userHelper,
        OrganizationProHelper $organizationHelper,
        TokenStorageInterface $tokenStorage
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->userHelper = $userHelper;
        $this->organizationHelper = $organizationHelper;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $organization = $this->getCurrentOrganization();

        if (!$organization) {
            return;
        }

        $config = $event->getConfig();

        // add left join
        $this->processSourceQueryJoins($config);

        // add select
        $this->processSourceQuerySelect($config);

        // add where condition
        $this->processSourceQueryWhere($config);

        if ($organization->getIsGlobal()) {
            $this->addOrganizationColumn($config);
            $this->addOrganizationFilter($config);
            $this->addOrganizationSorter($config);
        }
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function processSourceQueryJoins(DatagridConfiguration $config)
    {
        $from = $config->offsetGetByPath('[source][query][from]');
        $rootEntityAlias = $from[0]['alias'];

        $leftJoins = $config->offsetGetByPath('[source][query][join][left]', []);
        $leftJoins[] = [
            'join' => $rootEntityAlias . '.organization',
            'alias' => 'org'
        ];
        $config->offsetSetByPath('[source][query][join][left]', $leftJoins);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function processSourceQuerySelect(DatagridConfiguration $config)
    {
        $selects = $config->offsetGetByPath('[source][query][select]', []);
        $selects[] = 'org.name AS org_name';
        $config->offsetSetByPath('[source][query][select]', $selects);
    }

    /**
     * User is able to view all roles if he is assigned and logged in to Global Organization.
     * Otherwise user is able to view roles assigned to current organization or roles without organization.
     *
     * @param DatagridConfiguration $config
     */
    protected function processSourceQueryWhere(DatagridConfiguration $config)
    {
        $organization = $this->getCurrentOrganization();

        if ($organization->getIsGlobal() && $this->userHelper->isUserAssignedToOrganization($organization)) {
            return;
        }

        $config->offsetAddToArrayByPath(
            '[source][query][where][and]',
            [
                sprintf(
                    'org.id = %d OR org.id IS NULL',
                    $organization->getId()
                )
            ]
        );
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function addOrganizationColumn(DatagridConfiguration $config)
    {
        $columns = $config->offsetGetByPath('[columns]', []);
        $columns['org_name'] = [
            'label' => 'oro.user.role.organization.label',
            'type' => 'twig',
            'frontend_type' => 'html',
            'template' => 'OroProUserBundle:Role:Datagrid/Property/organization.html.twig',
        ];
        $config->offsetSetByPath('[columns]', $columns);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function addOrganizationFilter(DatagridConfiguration $config)
    {
        $filters = $config->offsetGetByPath('[filters][columns]', []);
        $filters['org_name'] = [
            'type'      => 'choice',
            'data_name' => 'org.id',
            'enabled'   => true,
            'options'   => [
                'field_options' => [
                    'choices'              => $this->organizationHelper->getOrganizationFilterChoices(),
                    'translatable_options' => false,
                    'multiple'             => true,
                ]
            ]
        ];
        $config->offsetSetByPath('[filters][columns]', $filters);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function addOrganizationSorter(DatagridConfiguration $config)
    {
        $sorters = $config->offsetGetByPath('[sorters][columns]', []);
        $sorters['org_name'] = [
            'data_name' => 'org_name'
        ];
        $config->offsetSetByPath('[sorters][columns]', $sorters);
    }

    /**
     * @return null|Organization
     */
    protected function getCurrentOrganization()
    {
        if (!$this->organization) {
            $token = $this->tokenStorage->getToken();

            if ($token instanceof OrganizationContextTokenInterface) {
                $this->organization = $token->getOrganizationContext();
            }
        }

        return $this->organization;
    }
}
