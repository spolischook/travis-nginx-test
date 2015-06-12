<?php
namespace Oro\Bundle\LDAPBundle\Provider\Connector;

use Oro\Bundle\IntegrationBundle\Provider\ForceConnectorInterface;
use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;

class UserLdapConnector implements ForceConnectorInterface, TwoWaySyncConnectorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return "oro.ldap.connector.user.label";
    }

    /**
     * {@inheritdoc}
     */
    public function getImportEntityFQCN()
    {
        return "Oro\Bundle\UserBundle\Entity\User";
    }

    /**
     * {@inheritdoc}
     */
    public function getImportJobName()
    {
        return "ldap_import_users";
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return "ldap";
    }

    /**
     * {@inheritdoc}
     */
    public function getExportJobName()
    {
        return "ldap_export_users";
    }

    /**
     * {@inheritdoc}
     */
    public function supportsForceSync()
    {
        return false;
    }
}
