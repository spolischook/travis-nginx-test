<?php
namespace Oro\Bundle\LDAPBundle\Provider\Connector;

use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;
use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;
use Oro\Bundle\UserBundle\Entity\User;

class UserLdapConnector extends AbstractConnector implements TwoWaySyncConnectorInterface
{

    const TYPE = 'user';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return "oro.ldap.integration.connector.user.label";
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
        return static::TYPE;
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
    protected function getConnectorSource()
    {
        return $this->transport->search($this->channel->getMappingSettings()->offsetGet('userFilter'));
    }

    /**
     * Generates distinguished name for user record/entity.
     *
     * @param array|User $user
     *
     * @return string
     */
    public function getDn($user)
    {
        $usernameAttr = $this->channel->getMappingSettings()->offsetGet('userMapping')['username'];
        $exportUserBaseDn = $this->channel->getMappingSettings()->offsetGet('exportUserBaseDn');

        if ($user instanceof User) {
            $dns = (array)$user->getLdapDistinguishedNames();

            if(isset($dns[$this->channel->getId()])) {
                return $dns[$this->channel->getId()];
            } else {
                return sprintf('%s=%s,%s', $usernameAttr, $user->getUsername(), $exportUserBaseDn);
            }
        }
        return $user['dn'];
    }
}
