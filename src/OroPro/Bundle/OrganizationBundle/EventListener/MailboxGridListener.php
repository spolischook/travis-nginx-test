<?php

namespace OroPro\Bundle\OrganizationBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EmailBundle\EventListener\Datagrid\MailboxGridListener as BaseMailboxGridListener;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class MailboxGridListener extends BaseMailboxGridListener
{
    const GLOBAL_CONFIG_ROUTE = 'oro_config_configuration_system';

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * {@inheritdoc} In addition it prevents defautl restrictions to be applied if organization is global
     */
    public function onBuildAfter(BuildAfter $event)
    {
        if ($this->isGlobalView($event)) {
            return;
        }

        parent::onBuildAfter($event);
    }

    /**
     * @param SecurityFacade $securityFacade
     */
    public function setSecurityFacade(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param BuildAfter $event
     *
     * @return bool
     */
    protected function isGlobalView(BuildAfter $event)
    {
        $config = $event->getDatagrid()->getConfig();
        $directParams = $config->offsetGetByPath(static::PATH_UPDATE_LINK_DIRECT_PARAMS, []);
        if (!isset($directParams[static::REDIRECT_DATA_KEY])) {
            return false;
        }

        if ($directParams[static::REDIRECT_DATA_KEY]['route'] !== 'oro_config_configuration_system') {
            return false;
        }

        $organization = $this->securityFacade->getOrganization();

        return $organization && $organization->getIsGlobal();
    }
}
