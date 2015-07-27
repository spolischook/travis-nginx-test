<?php
namespace OroCRMPro\Bundle\LDAPBundle\Provider;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class ChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    const TYPE = 'ldap';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrmpro.ldap.integration.channel.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        return 'bundles/orocrmproldap/img/ldap_logo.png';
    }
}
