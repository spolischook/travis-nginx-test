<?php

namespace OroCRMPro\Bundle\OutlookBundle\Entity\Manager;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EntityBundle\ORM\QueryBuilderHelper;
use Oro\Bundle\EntityBundle\ORM\QueryUtils;
use Oro\Bundle\EntityBundle\ORM\SqlQueryBuilder;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Event\FindAfter;
use Oro\Bundle\SoapBundle\Event\GetListBefore;

class EmailEntityApiEntityManager extends ApiEntityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var DQLNameFormatter */
    protected $nameFormatter;

    /**
     * @param string           $class
     * @param ObjectManager    $om
     * @param ActivityManager  $activityManager
     * @param DQLNameFormatter $nameFormatter
     */
    public function __construct(
        $class,
        ObjectManager $om,
        ActivityManager $activityManager,
        DQLNameFormatter $nameFormatter
    ) {
        parent::__construct($class, $om);
        $this->activityManager = $activityManager;
        $this->nameFormatter   = $nameFormatter;
    }

    /**
     * Returns the list of fields responsible to store activity associations for the given activity entity type
     *
     * @return array
     */
    public function getAssociations()
    {
        return $this->activityManager->getActivityTargets($this->class);
    }

    /**
     * Returns id of an email entity corresponding given criteria
     *
     * @param Criteria|array $criteria
     * @param array          $joins
     *
     * @return int|null
     */
    public function findEmailId($criteria, $joins)
    {
        $criteria = $this->normalizeQueryCriteria($criteria);

        $qb = $this->getRepository()->createQueryBuilder('e')
            ->select('partial e.{id}')
            ->setMaxResults(2);
        $this->applyJoins($qb, $joins);

        // fix of doctrine error with Same Field, Multiple Values, Criteria and QueryBuilder
        // http://www.doctrine-project.org/jira/browse/DDC-2798
        // TODO revert changes when doctrine version >= 2.5 in scope of BAP-5577
        QueryBuilderHelper::addCriteria($qb, $criteria);
        // $qb->addCriteria($criteria);

        /** @var Email[] $entity */
        $entity = $qb->getQuery()->getResult();
        if (!$entity || count($entity) > 1) {
            return null;
        }

        $this->checkFoundEntity($entity[0]);

        return $entity[0]->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getListQueryBuilder($limit = 10, $page = 1, $criteria = [], $orderBy = null, $joins = [])
    {
        $criteria = $this->normalizeQueryCriteria($criteria);

        $selectStmt = null;
        $subQueries = [];
        foreach ($this->getAssociations() as $entityClass => $fieldName) {
            // dispatch oro_api.request.get_list.before event
            $event = new GetListBefore($criteria, $entityClass);
            $this->eventDispatcher->dispatch(GetListBefore::NAME, $event);
            $subCriteria = $event->getCriteria();

            $nameExpr = $this->nameFormatter->getFormattedNameDQL('target', $entityClass);
            $subQb    = $this->getRepository()->createQueryBuilder('e')
                ->select(
                    sprintf(
                        'e.id AS emailId, target.%s AS entityId, \'%s\' AS entityClass, '
                        . ($nameExpr ?: '\'\'') . ' AS entityTitle',
                        $this->getIdentifierFieldName($entityClass),
                        str_replace('\'', '\'\'', $entityClass)
                    )
                )
                ->innerJoin('e.' . $fieldName, 'target');
            $this->applyJoins($subQb, $joins);

            // fix of doctrine error with Same Field, Multiple Values, Criteria and QueryBuilder
            // http://www.doctrine-project.org/jira/browse/DDC-2798
            // TODO revert changes when doctrine version >= 2.5 in scope of BAP-5577
            QueryBuilderHelper::addCriteria($subQb, $subCriteria);
            // $subQb->addCriteria($criteria);

            $subQuery = $subQb->getQuery();

            $subQueries[] = QueryUtils::getExecutableSql($subQuery);

            if (empty($selectStmt)) {
                $mapping    = QueryUtils::parseQuery($subQuery)->getResultSetMapping();
                $selectStmt = sprintf(
                    'entity.%s AS id, entity.%s AS entity, entity.%s AS title',
                    QueryUtils::getColumnNameByAlias($mapping, 'entityId'),
                    QueryUtils::getColumnNameByAlias($mapping, 'entityClass'),
                    QueryUtils::getColumnNameByAlias($mapping, 'entityTitle')
                );
            }
        }

        $rsm = new ResultSetMapping();
        $rsm
            ->addScalarResult('id', 'id', Type::INTEGER)
            ->addScalarResult('entity', 'entity')
            ->addScalarResult('title', 'title');
        $qb = new SqlQueryBuilder($this->getObjectManager(), $rsm);
        $qb
            ->select($selectStmt)
            ->from('(' . implode(' UNION ALL ', $subQueries) . ')', 'entity')
            ->setMaxResults($limit)
            ->setFirstResult($this->getOffset($page, $limit));
        if ($orderBy) {
            $qb->orderBy($orderBy);
        }

        return $qb;
    }

    /**
     * @param string $entityClass
     *
     * @return string
     */
    protected function getIdentifierFieldName($entityClass)
    {
        $metadata    = $this->getObjectManager()->getMetadataFactory()->getMetadataFor($entityClass);
        $identifiers = $metadata->getIdentifierFieldNames();

        return $identifiers[0];
    }
}
