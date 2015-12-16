<?php
namespace OroCRMPro\Bundle\LDAPBundle\Provider\Connector;

use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;
use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;

class UserLdapConnector extends AbstractConnector implements TwoWaySyncConnectorInterface
{

    const TYPE = 'user';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrmpro.ldap.integration.connector.user.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportEntityFQCN()
    {
        return 'Oro\Bundle\UserBundle\Entity\User';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportJobName()
    {
        return 'ldap_import_users';
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return static::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function getExportJobName()
    {
        return 'ldap_export_users';
    }

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        return $this->transport->search($this->channel->getMappingSettings()->offsetGet('userFilter'));
    }
}
