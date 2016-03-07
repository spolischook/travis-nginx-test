<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FieldsFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

class SetFieldsFilter implements ProcessorInterface
{
    const FILTER_KEY          = 'fields';
    const FILTER_KEY_TEMPLATE = 'fields[%s]';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * @param DoctrineHelper                  $doctrineHelper
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ValueNormalizer $valueNormalizer
    ) {
        $this->doctrineHelper         = $doctrineHelper;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $filters = $context->getFilters();
        if ($filters->has(self::FILTER_KEY)) {
            // filters have been already set
            return;
        }

        $this->addFilter($filters, $entityClass, $context->getRequestType());

        $associations = $context->getMetadata()->getAssociations();
        foreach ($associations as $association) {
            $targetClasses = $association->getAcceptableTargetClassNames();
            foreach ($targetClasses as $targetClass) {
                $this->addFilter($filters, $targetClass, $context->getRequestType());
            }
        }
    }

    /**
     * @param FilterCollection $filters
     * @param string           $entityClass
     * @param RequestType      $requestType
     */
    protected function addFilter(FilterCollection $filters, $entityClass, RequestType $requestType)
    {
        $entityType = $this->convertToEntityType($entityClass, $requestType);
        if ($entityType) {
            $filter = new FieldsFilter(
                DataType::STRING,
                sprintf('A list of fields for the \'%s\' entity to be returned.', $entityType)
            );
            $filter->setArrayAllowed(true);

            $filters->add(
                sprintf(self::FILTER_KEY_TEMPLATE, $entityType),
                $filter
            );
        }
    }

    /**
     * @param string      $entityClass
     * @param RequestType $requestType
     *
     * @return string|null
     */
    protected function convertToEntityType($entityClass, RequestType $requestType)
    {
        return ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            $entityClass,
            $requestType,
            false
        );
    }
}
