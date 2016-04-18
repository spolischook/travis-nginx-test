<?php

namespace OroCRMPro\Bundle\LDAPBundle\Provider\Transport;

use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

interface LdapTransportInterface extends TransportInterface
{
    /**
     * Searches for a record in LDAP.
     *
     * @param string|array $filter
     * @param array        $attributes
     *
     * @return \Iterator
     */
    public function search($filter, array $attributes = []);

    /**
     * Checks if record exists in LDAP
     *
     * @param string $dn
     *
     * @return bool
     */
    public function exists($dn);

    /**
     * Saves record to LDAP. If exists, it is updated, if not added.
     *
     * @param string $dn
     * @param array  $record
     */
    public function save($dn, array $record);

    /**
     * Updates record in LDAP.
     *
     * @param string $dn
     * @param array  $record
     */
    public function update($dn, array $record);

    /**
     * Adds record to LDAP
     *
     * @param string $dn
     * @param array  $record
     */
    public function add($dn, array $record);

    /**
     * Binds user to LDAP
     *
     * @param $username
     * @param $password
     *
     * @return bool
     */
    public function bind($username = null, $password = null);

    /**
     * Checks if able to connect to server.
     *
     * @return bool
     */
    public function check();
}
