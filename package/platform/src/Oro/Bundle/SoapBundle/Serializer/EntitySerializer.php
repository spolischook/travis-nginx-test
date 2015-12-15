<?php

namespace Oro\Bundle\SoapBundle\Serializer;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;
use Oro\Component\EntitySerializer\ConfigNormalizer;
use Oro\Component\EntitySerializer\DataAccessorInterface as BaseDataAccessorInterface;
use Oro\Component\EntitySerializer\DataNormalizer;
use Oro\Component\EntitySerializer\DataTransformerInterface as BaseDataTransformerInterface;
use Oro\Component\EntitySerializer\DoctrineHelper;
use Oro\Component\EntitySerializer\EntitySerializer as BaseEntitySerializer;
use Oro\Component\EntitySerializer\FieldAccessor;
use Oro\Component\EntitySerializer\QueryFactory;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Serializer\ExtendEntityFieldFilter;

/**
 * @deprecated since 1.9. use {@see Oro\Component\EntitySerializer\EntitySerializer}
 */
class EntitySerializer extends BaseEntitySerializer
{
    /**
     * @param ManagerRegistry              $doctrine
     * @param ConfigManager                $configManager
     * @param BaseDataAccessorInterface    $dataAccessor
     * @param BaseDataTransformerInterface $dataTransformer
     * @param QueryHintResolverInterface   $queryHintResolver
     */
    public function __construct(
        ManagerRegistry $doctrine,
        ConfigManager $configManager,
        BaseDataAccessorInterface $dataAccessor,
        BaseDataTransformerInterface $dataTransformer,
        QueryHintResolverInterface $queryHintResolver
    ) {
        $doctrineHelper = new DoctrineHelper($doctrine);
        $fieldAccessor  = new FieldAccessor(
            $doctrineHelper,
            $dataAccessor,
            new ExtendEntityFieldFilter($configManager)
        );
        parent::__construct(
            $doctrineHelper,
            $dataAccessor,
            $dataTransformer,
            new QueryFactory($doctrineHelper, $queryHintResolver),
            $fieldAccessor,
            new ConfigNormalizer(),
            new DataNormalizer()
        );
    }
}
