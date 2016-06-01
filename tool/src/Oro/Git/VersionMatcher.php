<?php

namespace Oro\Git;

class VersionMatcher
{
    /**
     * @param string $versionString
     *
     * @return string
     */
    public static function match($versionString)
    {
        if (!is_string($versionString)) {
            throw new \InvalidArgumentException('Invalid version');
        }

        preg_match('/(\d+\.)(\d+\.)(\*|\d+)/', $versionString, $matches);

        $version = reset($matches);

        if (!$version) {
            throw new \InvalidArgumentException('Invalid version');
        }

        return $version;
    }

    /**
     * @param string $actualVersion
     * @param string $expectedVersion
     *
     * @return bool
     */
    public static function gte($actualVersion, $expectedVersion)
    {
        $actualVersion = self::match($actualVersion);
        $expectedVersion = self::match($expectedVersion);

        return version_compare($actualVersion, $expectedVersion) !== -1;
    }
}
