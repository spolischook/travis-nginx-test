<?php

namespace OroPro\Bundle\OrganizationBundle\Grid;

use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroPro\Bundle\OrganizationBundle\Provider\SystemAccessModeOrganizationProvider;

class OrganizationColumnExtension extends AbstractExtension
{
    const COLUMN_NAME = 'organization_for_global_view_mode';

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ConfigManager */
    protected $configManager;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /** @var string|null */
    protected $entityClassName = null;

    /** @var SystemAccessModeOrganizationProvider */
    protected $organizationProvider;

    /**
     * @param SecurityFacade                       $securityFacade
     * @param ConfigManager                        $configManager
     * @param EntityClassResolver                  $entityClassResolver
     * @param SystemAccessModeOrganizationProvider $organizationProvider
     */
    public function __construct(
        SecurityFacade $securityFacade,
        ConfigManager $configManager,
        EntityClassResolver $entityClassResolver,
        SystemAccessModeOrganizationProvider $organizationProvider
    ) {
        $this->securityFacade       = $securityFacade;
        $this->configManager        = $configManager;
        $this->entityClassResolver  = $entityClassResolver;
        $this->organizationProvider = $organizationProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $organization = $this->securityFacade->getOrganization();

        return
            $organization
            && $organization->getIsGlobal()
            && (bool)$this->getOrganizationField($config);
    }

    /**
     * {@inheritdoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $organization = $this->securityFacade->getOrganization();
        if ($organization && $organization->getIsGlobal()) {
            $data->offsetAddToArray('gridViews', ['_sa_org_id' => $organization->getId()]);
        }

        $providedSystemOrganizationId = $this->organizationProvider->getOrganizationId();
        if ($providedSystemOrganizationId) {
            $data->offsetAddToArrayByPath(
                '[options][urlParams]',
                [
                    '_sa_org_id' => $providedSystemOrganizationId
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $entityClassName = $this->getEntity($config);

        /** @var QueryBuilder $qb */
        $qb        = $datasource->getQueryBuilder();
        $fromParts = $qb->getDQLPart('from');
        $alias     = false;

        /** @var From $fromPart */
        foreach ($fromParts as $fromPart) {
            if ($this->entityClassResolver->getEntityClass($fromPart->getFrom()) == $entityClassName) {
                $alias = $fromPart->getAlias();
                break;
            }
        }

        if ($alias === false) {
            // add entity if it not exists in from clause
            $alias = 'o';
            $qb->from($entityClassName, $alias);
        }

        // we should not add organization column if system access organization provider has organization
        if (!$this->organizationProvider->getOrganizationId()) {
            $qb->leftJoin(sprintf('%s.%s', $alias, $this->getOrganizationField($config)), 'org');
            $qb->addSelect('org.name as ' . self::COLUMN_NAME);

            $groupBy = $qb->getDQLPart('groupBy');
            if (!empty($groupBy)) {
                $qb->addGroupBy('org.name');
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function processConfigs(DatagridConfiguration $config)
    {
        // we should not add organization column if system access organization provider has organization
        if ($this->organizationProvider->getOrganizationId()) {
            return;
        }

        /**
         * add column
         */
        $columns = $config->offsetGetByPath('[columns]') ? : [];
        $config->offsetSetByPath(
            '[columns]',
            array_merge(
                [
                    self::COLUMN_NAME => [
                        'label'         => 'oro.organization.entity_label',
                        'type'          => 'field',
                        'frontend_type' => 'string',
                        'translatable'  => true,
                        'editable'      => false,
                        'renderable'    => true
                    ]
                ],
                $columns
            )
        );

        /**
         * configure column sorter
         */
        $sorters = $config->offsetGetByPath('[sorters][columns]') ? : [];
        $config->offsetSetByPath(
            '[sorters][columns]',
            array_merge(
                [
                    self::COLUMN_NAME => [
                        'data_name' => self::COLUMN_NAME
                    ]
                ],
                $sorters
            )
        );

        /*
         * Three cases:
         * 1. set sort by organization if default sorting doesn't exist
         * 2. merge sort by organization with default sorting if multiple_sorting=true
         * 3. set default sorting if multiple_sorting=false
         */
        $multiSort = $config->offsetGetByPath('[sorters][multiple_sorting]');
        $sortByOrganization = [self::COLUMN_NAME => 'ASC'];
        $defaultSorters = $config->offsetGetByPath('[sorters][default]', $sortByOrganization);
        if ($multiSort) {
            $config->offsetSetByPath(
                '[sorters][default]',
                array_merge($sortByOrganization, $defaultSorters)
            );
        } else {
            $config->offsetSetByPath('[sorters][default]', $defaultSorters);
        }

        /**
         * configure column filter
         */
        $filters = $config->offsetGetByPath('[filters][columns]') ? : [];
        $config->offsetSetByPath(
            '[filters][columns]',
            array_merge(
                [
                    self::COLUMN_NAME => [
                        'type'         => 'entity',
                        'data_name'    => 'org.id',
                        'enabled'      => true,
                        'translatable' => true,
                        'options'      => [
                            'field_options' => [
                                'class'                => 'OroOrganizationBundle:Organization',
                                'property'             => 'name',
                                'multiple'             => true,
                                'translatable_options' => true
                            ]
                        ]
                    ]
                ],
                $filters
            )
        );
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return null|string
     */
    protected function getEntity(DatagridConfiguration $config)
    {
        if ($this->entityClassName === null) {
            $entityClassName = $config->offsetGetByPath('[extended_entity_name]');
            if (!$entityClassName) {
                $from = $config->offsetGetByPath('[source][query][from]');
                if (!$from) {
                    return null;
                }

                $entityClassName = $this->entityClassResolver->getEntityClass($from[0]['table']);
            }

            $this->entityClassName = $entityClassName;
        }

        return $this->entityClassName;
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return string|null
     */
    protected function getOrganizationField(DatagridConfiguration $config)
    {
        $entityClassName   = $this->getEntity($config);
        $ownershipProvider = $this->configManager->getProvider('ownership');
        if ($entityClassName && $ownershipProvider->hasConfig($entityClassName)) {
            $ownershipConfig = $ownershipProvider->getConfig($entityClassName);
            switch ($ownershipConfig->get('owner_type')) {
                case 'USER':
                case 'BUSINESS_UNIT':
                    return $ownershipConfig->get('organization_field_name');
                case 'ORGANIZATION':
                    return $ownershipConfig->get('owner_field_name');
                default:
                    return null;
            }
        }

        return null;
    }
}
