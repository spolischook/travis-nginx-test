<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class EntityTypeSecurityCheck implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var string */
    protected $permission;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param SecurityFacade $securityFacade
     * @param string         $permission
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        SecurityFacade $securityFacade,
        $permission
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->securityFacade = $securityFacade;
        $this->permission = $permission;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $config = $context->getConfig();

        $isGranted = true;
        if (!$config || !$config->hasAclResource()) {
            $entityClass = $context->getClassName();
            if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
                $isGranted = $this->securityFacade->isGranted(
                    $this->permission,
                    new ObjectIdentity('entity', $entityClass)
                );
            }
        } else {
            $aclResource = $config->getAclResource();
            if ($aclResource) {
                $isGranted = $this->securityFacade->isGranted($aclResource);
            }
        }

        if (!$isGranted) {
            throw new AccessDeniedException();
        }
    }
}
