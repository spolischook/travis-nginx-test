<?php

namespace OroB2B\Bundle\ProductBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\ParameterBag;

use Doctrine\ORM\QueryBuilder;

class ProductSelectDBQueryEvent extends Event
{
    const NAME = 'orob2b_product.product_select.db.query';

    /** @var QueryBuilder */
    protected $queryBuilder;

    /** @var ParameterBag */
    protected $dataParameters;

    /**
     * @param QueryBuilder $queryBuilder
     * @param ParameterBag $dataParameters
     */
    public function __construct(QueryBuilder $queryBuilder, ParameterBag $dataParameters)
    {
        $this->queryBuilder = $queryBuilder;
        $this->dataParameters = $dataParameters;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @return ParameterBag
     */
    public function getDataParameters()
    {
        return $this->dataParameters;
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }
}
