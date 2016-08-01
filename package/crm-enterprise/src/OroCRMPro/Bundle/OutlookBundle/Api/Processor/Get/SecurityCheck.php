<?php

namespace OroCRMPro\Bundle\OutlookBundle\Api\Processor\Get;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * Validates whether an access to Outlook related configuration is granted.
 */
class SecurityCheck implements ProcessorInterface
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        /**
         * enabled "Outlook Integration" capability is enough to access to Outlook related configuration
         */
        if ($this->isOutlookConfiguration($context->getId())
            && $this->securityFacade->isGranted('orocrmpro_outlook_integration')
        ) {
            $context->skipGroup('security_check');
        }
    }

    /**
     * @param string $sectionId
     *
     * @return bool
     */
    protected function isOutlookConfiguration($sectionId)
    {
        return
            'outlook' === $sectionId
            || 0 === strpos($sectionId, 'outlook.');
    }
}
