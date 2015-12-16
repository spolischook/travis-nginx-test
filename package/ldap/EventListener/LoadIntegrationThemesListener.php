<?php

namespace OroCRMPro\Bundle\LDAPBundle\EventListener;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\LoadIntegrationThemesEvent;

use OroCRMPro\Bundle\LDAPBundle\Provider\ChannelType;

class LoadIntegrationThemesListener
{
    const LDAP_THEME = 'OroCRMProLDAPBundle:Form:fields.html.twig';

    /**
     * @param LoadIntegrationThemesEvent $event
     */
    public function onLoad(LoadIntegrationThemesEvent $event)
    {
        $formView = $event->getFormView();
        if (!isset($formView->vars['value']) || !$formView->vars['value'] instanceof Channel) {
            return;
        }

        $channel = $formView->vars['value'];
        if ($channel->getType() !== ChannelType::TYPE) {
            return;
        }

        $event->addTheme(static::LDAP_THEME);
    }
}
