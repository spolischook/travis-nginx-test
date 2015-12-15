<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RestRequest;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

/**
 * Sets "include" filter into the Context.
 */
class NormalizeIncludeParameter implements ProcessorInterface
{
    const FILTER_KEY = 'include';

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if (!$context->hasConfigExtra(ExpandRelatedEntitiesConfigExtra::NAME)) {
            $filterValue = $context->getFilterValues()->get(self::FILTER_KEY);
            if (null !== $filterValue) {
                $includes = $this->valueNormalizer->normalizeValue(
                    $filterValue->getValue(),
                    DataType::STRING,
                    $context->getRequestType(),
                    true
                );
                if (!empty($includes)) {
                    $context->addConfigExtra(new ExpandRelatedEntitiesConfigExtra((array)$includes));
                }
            }
        }
    }
}
