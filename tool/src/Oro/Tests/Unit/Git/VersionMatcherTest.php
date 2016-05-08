<?php

namespace Oro\Tests\Unit\Git;

use Oro\Git\VersionMatcher;

class VersionMatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $actualVersion
     * @param bool $result
     *
     * @dataProvider versionDataProvider
     */
    public function testMatch($actualVersion, $result)
    {
        $this->assertEquals(
            $result,
            VersionMatcher::match($actualVersion)
        );
    }

    /**
     * @return array
     */
    public function versionDataProvider()
    {
        return [
            ['git version 2.7.2', '2.7.2'],
            ['git version 2.6.4 (Apple Git-63)', '2.6.4'],
            ['git version 1.9.5', '1.9.5'],
            ['git version 1.9.1', '1.9.1'],
        ];
    }

    /**
     * @param string $invalidVersion
     *
     * @dataProvider invalidVersionDataProvider
     *
     * @expectedException \InvalidArgumentException
     */
    public function testMatchNotString($invalidVersion)
    {
        VersionMatcher::match($invalidVersion);
    }

    /**
     * @return array
     */
    public function invalidVersionDataProvider()
    {
        return [
            ['git version 2.7'],
            ['git version 2'],
            ['git version 2.6 (Apple Git-63)'],
            ['git version 2 (Apple Git-63)'],
            ['git version 1.9'],
            ['git version 1'],
        ];
    }

    /**
     * @param string $actualVersion
     * @param bool $result
     *
     * @dataProvider gteDataProvider
     */
    public function testGte($actualVersion, $result)
    {
        $this->assertEquals(
            $result,
            VersionMatcher::gte($actualVersion, '2.0.0')
        );
    }

    /**
     * @return array
     */
    public function gteDataProvider()
    {
        return [
            ['2.7.2', true],
            ['2.6.4', true],
            ['2.0.0', true],
            ['1.9.5', false],
            ['1.9.1', false],
        ];
    }
}
