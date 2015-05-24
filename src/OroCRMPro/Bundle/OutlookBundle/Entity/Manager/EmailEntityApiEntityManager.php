<?php

namespace OroCRMPro\Bundle\OutlookBundle\Entity\Manager;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EntityBundle\ORM\QueryBuilderHelper;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Entity\Manager\EntitySerializerManagerInterface;
use Oro\Bundle\SoapBundle\Event\FindAfter;
use Oro\Bundle\SoapBundle\Serializer\EntitySerializer;

class EmailEntityApiEntityManager extends ApiEntityManager implements EntitySerializerManagerInterface
{
    /** @var EntitySerializer */
    protected $entitySerializer;

    /** @var ActivityManager */
    protected $activityManager;

    /**
     * @param string           $class
     * @param ObjectManager    $om
     * @param EntitySerializer $entitySerializer
     * @param ActivityManager  $activityManager
     */
    public function __construct(
        $class,
        ObjectManager $om,
        EntitySerializer $entitySerializer,
        ActivityManager $activityManager
    ) {
        parent::__construct($class, $om);
        $this->entitySerializer = $entitySerializer;
        $this->activityManager  = $activityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(QueryBuilder $qb)
    {
        return $qb->getQuery()->getArrayResult();
    }

    /**
     * {@inheritdoc}
     */
    public function serializeOne($id)
    {
        throw new \BadMethodCallException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function getListQueryBuilder($limit = 10, $page = 1, $criteria = [], $orderBy = null, $joins = [])
    {
        $associations      = getAssociations();
        $targetIdentifiers = [];

        foreach ($associations as $entityClass => $fieldName) {
            $identifiers = $this->om->getClassMetadata($entityClass)->getIdentifierFieldNames();
            if (count($identifiers) === 1) {
                $targetIdentifiers[] = $fieldName . '.' . $identifiers[0];
                $joins[]             = $fieldName;
            }
        }

        $qb = parent::getListQueryBuilder($limit, $page, $criteria, $orderBy, $joins);

        $qb->select('e.id as id')
            ->addSelect('COALESCE(' . implode(',', $targetIdentifiers) . ') AS targetId');

        return $qb;
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

        // dispatch oro_api.request.find.after event
        $event = new FindAfter($entity[0]);
        $this->eventDispatcher->dispatch(FindAfter::NAME, $event);

        return $entity[0]->getId();
    }

    /**
     * @return array
     */
    protected function getSerializationConfig()
    {
        $config = [
            'exclusion_policy' => 'all',
            'fields'           => 'id'
        ];

        return $config;
    }
}
