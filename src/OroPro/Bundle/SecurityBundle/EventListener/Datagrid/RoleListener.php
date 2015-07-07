<?php

namespace OroPro\Bundle\SecurityBundle\EventListener\Datagrid;

use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

class RoleListener
{
    const ORGANIZATION_FIELD = 'organization';
    const ORGANIZATION_ALIAS = 'org';
    const ORGANIZATION_NAME_COLUMN = 'name';

    /**
     * @var ServiceLink
     */
    protected $securityContextLink;

    /**
     * @param ServiceLink $securityContextLink
     */
    public function __construct(ServiceLink $securityContextLink)
    {
        $this->securityContextLink = $securityContextLink;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();

        // add left join
        $this->processSourceQueryJoins($config);

        // add select
        $this->processSourceQuerySelect($config);

        // add where condition
        $this->processSourceQueryWhere($config);

        // add column
        $this->processColumns($config);

        $this->addOrganizationFilter($config);

        $this->addOrganizationSorter($config);
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
            'join' => $rootEntityAlias . '.' . self::ORGANIZATION_FIELD,
            'alias' => self::ORGANIZATION_ALIAS
        ];
        $config->offsetSetByPath('[source][query][join][left]', $leftJoins);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function processSourceQuerySelect(DatagridConfiguration $config)
    {
        $organizationSelect = self::ORGANIZATION_ALIAS . '.' . self::ORGANIZATION_NAME_COLUMN;
        $selects = $config->offsetGetByPath('[source][query][select]', []);
        $selects[] = $organizationSelect;
        $config->offsetSetByPath('[source][query][select]', $selects);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function processSourceQueryWhere(DatagridConfiguration $config)
    {
        /** @var SecurityContextInterface $securityContext */
        $securityContext = $this->securityContextLink->getService();
        $user = $securityContext->getToken()->getUser();
        $organizations = $user->getOrganizations();

        $where = $config->offsetGetByPath('[source][query][where][and]', []);

        $param = self::ORGANIZATION_ALIAS . '.' . 'id';
        // User in not assigned to any Organization
        if (empty($organizations)) {
            $where = array_merge(
                $where,
                [$param . ' IS NULL']
            );
        }

        $globalAccess = false;
        $organizationsIds = [];
        foreach ($organizations as $organization) {
            if ($organization->getIsGlobal() == true) {
                // User assigned to the System Organization
                $globalAccess = true;
                break;
            }
            $organizationsIds[] = $organization->getId();
        }

        // Restrict access only to the organizations where user is assigned
        if (!$globalAccess && !empty($organizations)) {
            $where = array_merge(
                $where,
                [$param . ' IN (' . implode(',', $organizationsIds) . ') OR ' . $param . ' IS NULL']
            );
        }

        if (count($where)) {
            $config->offsetSetByPath('[source][query][where][and]', $where);
        }
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function processColumns(DatagridConfiguration $config)
    {
        $columns = $config->offsetGetByPath('[columns]', []);
        $columns[self::ORGANIZATION_NAME_COLUMN] = ['label' => 'oropro.security_config.role.organization.grid.label'];
        $config->offsetSetByPath('[columns]', $columns);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function addOrganizationFilter(DatagridConfiguration $config)
    {
        $filters = $config->offsetGetByPath('[filters][columns]', []);
        $filters[self::ORGANIZATION_NAME_COLUMN] = [
            'type' => 'string',
            'data_name' => self::ORGANIZATION_ALIAS . '.' . self::ORGANIZATION_NAME_COLUMN,
        ];
        $config->offsetSetByPath('[filters][columns]', $filters);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function addOrganizationSorter(DatagridConfiguration $config)
    {
        $sorters = $config->offsetGetByPath('[sorters][columns]', []);
        $sorters[self::ORGANIZATION_NAME_COLUMN] = [
            'data_name' => self::ORGANIZATION_ALIAS . '.' . self::ORGANIZATION_NAME_COLUMN
        ];
        $config->offsetSetByPath('[sorters][columns]', $sorters);
    }
}
