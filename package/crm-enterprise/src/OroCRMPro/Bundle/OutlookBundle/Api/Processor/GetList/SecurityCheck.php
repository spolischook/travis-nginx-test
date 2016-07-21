<?php

namespace OroCRMPro\Bundle\OutlookBundle\Api\Processor\GetList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\ListContext;
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
        /** @var ListContext $context */

        /**
         * enabled "Outlook Integration" capability is enough to access to Outlook related configuration
         * filtering of not accessible data is performed during the loading
         * @see Oro\Bundle\ConfigBundle\Api\Processor\GetList\LoadConfigurationSections
         * @see OroCRMPro\Bundle\OutlookBundle\Api\Processor\GetList\LoadConfigurationSections
         */
        if ($this->securityFacade->isGranted('orocrmpro_outlook_integration')) {
            $context->skipGroup('security_check');
        }
    }
}
