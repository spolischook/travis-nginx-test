<?php

namespace Oro\Bundle\QueryDesignerBundle\Grid\Extension;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilderInterface;

class OrmDatasourceExtension extends AbstractExtension
{
    const NAME_PATH = '[name]';

    /**
     * @var string[]
     */
    protected $appliedFor;

    /** @var RestrictionBuilderInterface */
    protected $restrictionBuilder;

    /**
     * @param RestrictionBuilderInterface $restrictionBuilder
     */
    public function __construct(RestrictionBuilderInterface $restrictionBuilder)
    {
        $this->restrictionBuilder = $restrictionBuilder;
        $this->parameters = new ParameterBag();
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->getDatasourceType() == OrmDatasource::TYPE
            && $config->offsetGetByPath('[source][query_config][filters]');
    }

    /**
     * {@inheritdoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $gridName = $config->offsetGetByPath(self::NAME_PATH);
        $parametersKey = md5(json_encode($this->parameters->all()));

        if (!empty($this->appliedFor[$gridName . $parametersKey])) {
            return;
        }

        /** @var QueryBuilder $qb */
        $qb      = $datasource->getQueryBuilder();
        $ds      = new GroupingOrmFilterDatasourceAdapter($qb);
        $filters = $config->offsetGetByPath('[source][query_config][filters]');
        $this->restrictionBuilder->buildRestrictions($filters, $ds);
        $this->appliedFor[$gridName . $parametersKey] = true;
    }
}
