<?php

namespace Oro\Bundle\LDAPBundle\ImportExport\Utils;

class LdapUtils
{
    const USERNAME_MAPPING_ATTRIBUTE = 'username';

    /**
     * Generates distinguished name of user.
     *
     * @param string      $usernameAttribute
     * @param string      $username
     * @param null|string $baseDn
     *
     * @return string
     */
    public static function createDn($usernameAttribute, $username, $baseDn = null)
    {
        if ($baseDn === null) {
            return sprintf('%s=%s', $usernameAttribute, $username);
        }

        return sprintf('%s=%s,%s', $usernameAttribute, $username, $baseDn);
    }

    /**
     * Adds searched attribute to an existing ldap filter.
     *
     * @param string $attribute Attribute, we are searching for
     * @param string $value     Value of attribute
     * @param string $filter    Additional filter(s)
     *
     * @return string
     */
    public static function getSearchFilter($attribute, $value, $filter)
    {
        if (!preg_match('/^\(.+\)$/', $filter)) {
            $filter = "($filter)";
        }

        return sprintf('(&%s(%s=%s))', $filter, $attribute, $value);
    }
}
