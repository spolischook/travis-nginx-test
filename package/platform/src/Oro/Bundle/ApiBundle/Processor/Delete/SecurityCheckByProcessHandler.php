<?php

namespace Oro\Bundle\ApiBundle\Processor\Delete;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\ChainProcessor\ContextInterface;

class SecurityCheckByProcessHandler implements ProcessorInterface
{
    /** @var DeleteHandler */
    protected $deleteHandler;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DeleteHandler $deleteHandler
     */
    public function __construct(DoctrineHelper $doctrineHelper, DeleteHandler $deleteHandler)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->deleteHandler = $deleteHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var DeleteContext $context */

        if (!$context->hasObject()) {
            // context has no object
            return;
        }

        $object = $context->getObject();

        if (!is_object($object)) {
            // given object data is not an object
            return;
        }


        $this->deleteHandler->isDeleteGranted($object, $this->doctrineHelper->getEntityManager($object));
        $context->setSecurityChecked();
    }
}