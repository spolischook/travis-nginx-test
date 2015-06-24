<?php

namespace Oro\Bundle\LDAPBundle\Provider\Transport;

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
     * Gets record by its distinguished name.
     *
     * @param string $dn
     *
     * @return array
     */
    public function get($dn);

    /**
     * Moves record to a different location.
     *
     * @param string $from
     * @param string $to
     */
    public function move($from, $to);

    /**
     * Removes record from LDAP.
     *
     * @param string $dn
     */
    public function remove($dn);

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
}
