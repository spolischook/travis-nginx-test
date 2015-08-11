<?php
namespace OroCRMPro\Bundle\LDAPBundle\Provider\Transport;

use Symfony\Component\HttpFoundation\ParameterBag;

use Zend\Ldap\Exception\LdapException;
use Zend\Ldap\Ldap;

use Oro\Bundle\IntegrationBundle\Entity\Transport;

class LdapTransport implements LdapTransportInterface
{
    /** @var ParameterBag */
    protected $settings;

    /** @var Ldap */
    protected $ldap;

    /**
     * {@inheritdoc}
     */
    public function init(Transport $transportEntity)
    {
        $this->settings = $transportEntity->getSettingsBag();
        $options = iterator_to_array($this->settings->getIterator());

        // Convert choice of encryption to flags.
        if ($options['encryption'] === 'ssl') {
            $options['useSsl'] = true;
        } elseif ($options['encryption'] === 'tls') {
            $options['useStartTls'] = true;
        }
        unset($options['encryption']);
        unset($options['page_size']);

        $this->ldap = new Ldap($options);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'orocrmpro.ldap.transport.ldap.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsFormType()
    {
        return 'orocrmpro_ldap_ldap_transport_setting_form_type';
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsEntityFQCN()
    {
        return 'OroCRMPro\Bundle\LDAPBundle\Entity\LdapTransport';
    }

    /**
     * {@inheritdoc}
     */
    public function search($filter, array $attributes = [])
    {
        return $this->ldap->search($filter, null, Ldap::SEARCH_SCOPE_SUB, $attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function exists($dn)
    {
        return $this->ldap->exists($dn);
    }

    /**
     * {@inheritdoc}
     */
    public function save($dn, array $record)
    {
        $this->ldap->save($dn, $record);
    }

    /**
     * {@inheritdoc}
     */
    public function update($dn, array $record)
    {
        $this->ldap->update($dn, $record);
    }

    /**
     * {@inheritdoc}
     */
    public function add($dn, array $record)
    {
        $this->ldap->add($dn, $record);
    }

    /**
     * {@inheritdoc}
     */
    public function bind($username = null, $password = null)
    {
        try {
            $this->ldap->bind($username, $password);
        } catch (LdapException $e) {
            return false;
        }

        return $username === $this->ldap->getBoundUser();
    }

    /**
     * {@inheritdoc}
     */
    public function check()
    {
        try {
            return $this->ldap->bind();
        } catch (\Exception $e) {
            return false;
        }
    }
}
