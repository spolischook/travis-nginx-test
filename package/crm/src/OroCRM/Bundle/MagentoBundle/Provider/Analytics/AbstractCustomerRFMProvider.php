<?php

namespace OroCRM\Bundle\MagentoBundle\Provider\Analytics;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use OroCRM\Bundle\AnalyticsBundle\Builder\RFMProviderInterface;
use OroCRM\Bundle\AnalyticsBundle\Model\RFMAwareInterface;
use OroCRM\Bundle\ChannelBundle\Model\CustomerIdentityInterface;

abstract class AbstractCustomerRFMProvider implements RFMProviderInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $className;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string $className
     */
    public function __construct(DoctrineHelper $doctrineHelper, $className)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->className = $className;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity)
    {
        return $entity instanceof RFMAwareInterface
            && $entity instanceof CustomerIdentityInterface
            && $entity instanceof $this->className;
    }
}
