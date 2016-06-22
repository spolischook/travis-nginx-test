<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\GetSubresource;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\CustomizeLoadedDataConfigExtra;
use Oro\Bundle\ApiBundle\Config\DataTransformersConfigExtra;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;

/**
 * Sets an initial list of requests for configuration data.
 */
class InitializeConfigExtras implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        $context->addConfigExtra(new EntityDefinitionConfigExtra($context->getAction()));
        $context->addConfigExtra(new CustomizeLoadedDataConfigExtra());
        $context->addConfigExtra(new DataTransformersConfigExtra());
        if ($context->isCollection()) {
            $context->addConfigExtra(new FiltersConfigExtra());
            $context->addConfigExtra(new SortersConfigExtra());
        }
    }
}
