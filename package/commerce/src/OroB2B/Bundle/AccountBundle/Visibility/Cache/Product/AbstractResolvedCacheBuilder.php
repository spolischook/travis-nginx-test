<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\BasicOperationRepositoryTrait;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;

abstract class AbstractResolvedCacheBuilder implements CacheBuilderInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertFromSelectQueryExecutor;

    /**
     * @var string
     */
    protected $cacheClass;

    /**
     * @var int
     */
    protected $visibilityFromConfig;

    /**
     * @param ManagerRegistry $registry
     * @param InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
     */
    public function __construct(
        ManagerRegistry $registry,
        InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
    ) {
        $this->registry = $registry;
        $this->insertFromSelectQueryExecutor = $insertFromSelectQueryExecutor;
    }

    /**
     * @param string $cacheClass
     */
    public function setCacheClass($cacheClass)
    {
        $this->cacheClass = $cacheClass;
    }

    /**
     * @param string $selectedVisibility
     * @param VisibilityInterface $productVisibility|null
     * @return array
     */
    protected function resolveStaticValues($selectedVisibility, VisibilityInterface $productVisibility = null)
    {
        $updateData = [
            'sourceProductVisibility' => $productVisibility,
            'source' => BaseProductVisibilityResolved::SOURCE_STATIC,
            'category' => null,
        ];

        if ($selectedVisibility === VisibilityInterface::VISIBLE) {
            $updateData['visibility'] = BaseVisibilityResolved::VISIBILITY_VISIBLE;
        } elseif ($selectedVisibility === VisibilityInterface::HIDDEN) {
            $updateData['visibility'] = BaseVisibilityResolved::VISIBILITY_HIDDEN;
        }

        return $updateData;
    }

    /**
     * @param EntityRepository|BasicOperationRepositoryTrait $repository
     * @param bool $insert
     * @param bool $delete
     * @param array $update
     * @param array $where
     */
    protected function executeDbQuery(EntityRepository $repository, $insert, $delete, array $update, array $where)
    {
        if ($insert) {
            $repository->insertEntity(array_merge($update, $where));
        } elseif ($delete) {
            $repository->deleteEntity($where);
        } elseif ($update) {
            $repository->updateEntity($update, $where);
        }
    }

    /**
     * @param boolean $isVisible
     * @return integer
     */
    protected function convertVisibility($isVisible)
    {
        return $isVisible ? BaseVisibilityResolved::VISIBILITY_VISIBLE
            : BaseVisibilityResolved::VISIBILITY_HIDDEN;
    }

    /**
     * @param string $visibility
     * @return int
     */
    protected function convertStaticVisibility($visibility)
    {
        return $visibility === VisibilityInterface::VISIBLE
            ? BaseVisibilityResolved::VISIBILITY_VISIBLE
            : BaseVisibilityResolved::VISIBILITY_HIDDEN;
    }

    /**
     * @param object $entity
     * @return object|null
     */
    protected function refreshEntity($entity)
    {
        $entityClass = ClassUtils::getClass($entity);
        /** @var EntityManager $entityManager */
        $entityManager = $this->registry->getManagerForClass($entityClass);

        if ($entityManager->getUnitOfWork()->getEntityState($entity) !== UnitOfWork::STATE_MANAGED) {
            $identifier = $entityManager->getClassMetadata($entityClass)->getIdentifierValues($entity);
            if ($identifier) {
                $entity = $entityManager->getRepository($entityClass)->find($identifier);
            } else {
                $entity = null;
            }
        } else {
            $entityManager->refresh($entity);
        }

        return $entity;
    }

    /**
     * Use category ID as array index
     *
     * @param array $visibilities
     * @param string $fieldName
     * @return array
     */
    protected function indexVisibilities(array $visibilities, $fieldName)
    {
        $indexedVisibilities = [];
        foreach ($visibilities as $visibility) {
            $index = $visibility[$fieldName];
            $indexedVisibilities[$index] = $visibility;
        }

        return $indexedVisibilities;
    }
}
