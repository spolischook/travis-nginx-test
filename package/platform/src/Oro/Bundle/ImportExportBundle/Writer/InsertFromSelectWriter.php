<?php

namespace Oro\Bundle\ImportExportBundle\Writer;

use Doctrine\ORM\Query;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

class InsertFromSelectWriter extends AbstractNativeQueryWriter
{
    /**
     * @var array
     */
    protected $fields;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertFromSelectQueryExecutor;

    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelectQuery
     */
    public function __construct(InsertFromSelectQueryExecutor $insertFromSelectQuery)
    {
        $this->insertFromSelectQueryExecutor = $insertFromSelectQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        foreach ($items as $item) {
            if ($this instanceof CleanUpInterface) {
                $this->cleanUp($item);
            }

            $this->insertFromSelectQueryExecutor->execute(
                $this->entityName,
                $this->fields,
                $this->getQueryBuilder($item)
            );
        }
    }

    /**
     * @param array $fields
     * @return $this
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }
}
