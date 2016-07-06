<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;

use OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipProMetadata;

class OwnershipProMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWithoutParameters()
    {
        $metadata = new OwnershipProMetadata();
        $this->assertFalse($metadata->hasOwner());
        $this->assertFalse($metadata->isOrganizationOwned());
        $this->assertFalse($metadata->isGlobalLevelOwned());
        $this->assertFalse($metadata->isBusinessUnitOwned());
        $this->assertFalse($metadata->isLocalLevelOwned());
        $this->assertFalse($metadata->isUserOwned());
        $this->assertFalse($metadata->isBasicLevelOwned());
        $this->assertEquals('', $metadata->getOwnerFieldName());
        $this->assertEquals('', $metadata->getOwnerColumnName());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorWithInvalidOwnerType()
    {
        new OwnershipProMetadata('test');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorWithoutOwnerFieldName()
    {
        new OwnershipProMetadata('ORGANIZATION');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorWithoutOwnerIdColumnName()
    {
        new OwnershipProMetadata('ORGANIZATION', 'org');
    }

    public function testOrganizationOwnership()
    {
        $metadata = new OwnershipProMetadata('ORGANIZATION', 'org', 'org_id', '', '', true);

        $this->assertEquals(OwnershipProMetadata::OWNER_TYPE_ORGANIZATION, $metadata->getOwnerType());
        $this->assertTrue($metadata->hasOwner());
        $this->assertTrue($metadata->isOrganizationOwned());
        $this->assertTrue($metadata->isGlobalLevelOwned());
        $this->assertFalse($metadata->isBusinessUnitOwned());
        $this->assertFalse($metadata->isLocalLevelOwned());
        $this->assertFalse($metadata->isUserOwned());
        $this->assertFalse($metadata->isBasicLevelOwned());
        $this->assertEquals('org', $metadata->getOwnerFieldName());
        $this->assertEquals('org_id', $metadata->getOwnerColumnName());
        $this->assertTrue($metadata->isGlobalView());
    }

    public function testBusinessUnitOwnership()
    {
        $metadata = new OwnershipProMetadata('BUSINESS_UNIT', 'bu', 'bu_id', '', '', true);
        $this->assertEquals(OwnershipProMetadata::OWNER_TYPE_BUSINESS_UNIT, $metadata->getOwnerType());
        $this->assertTrue($metadata->hasOwner());
        $this->assertFalse($metadata->isOrganizationOwned());
        $this->assertFalse($metadata->isGlobalLevelOwned());
        $this->assertTrue($metadata->isBusinessUnitOwned());
        $this->assertTrue($metadata->isLocalLevelOwned());
        $this->assertFalse($metadata->isUserOwned());
        $this->assertFalse($metadata->isBasicLevelOwned());
        $this->assertEquals('bu', $metadata->getOwnerFieldName());
        $this->assertEquals('bu_id', $metadata->getOwnerColumnName());
        $this->assertTrue($metadata->isGlobalView());
    }

    public function testUserOwnership()
    {
        $metadata = new OwnershipProMetadata('USER', 'usr', 'user_id');
        $this->assertEquals(OwnershipProMetadata::OWNER_TYPE_USER, $metadata->getOwnerType());
        $this->assertTrue($metadata->hasOwner());
        $this->assertFalse($metadata->isOrganizationOwned());
        $this->assertFalse($metadata->isGlobalLevelOwned());
        $this->assertFalse($metadata->isBusinessUnitOwned());
        $this->assertFalse($metadata->isLocalLevelOwned());
        $this->assertTrue($metadata->isUserOwned());
        $this->assertTrue($metadata->isBasicLevelOwned());
        $this->assertEquals('usr', $metadata->getOwnerFieldName());
        $this->assertEquals('user_id', $metadata->getOwnerColumnName());
        $this->assertFalse($metadata->isGlobalView());
    }

    public function testSerialization()
    {
        $metadata = new OwnershipProMetadata('ORGANIZATION', 'org', 'org_id');
        $data = serialize($metadata);
        $metadata = new OwnershipProMetadata();
        $this->assertFalse($metadata->isOrganizationOwned());
        $this->assertFalse($metadata->isGlobalLevelOwned());
        $this->assertEquals('', $metadata->getOwnerFieldName());
        $this->assertEquals('', $metadata->getOwnerColumnName());
        $metadata = unserialize($data);
        $this->assertTrue($metadata->isOrganizationOwned());
        $this->assertEquals('org', $metadata->getOwnerFieldName());
        $this->assertEquals('org_id', $metadata->getOwnerColumnName());
        $this->assertFalse($metadata->isGlobalView());
    }

    /**
     * @dataProvider getAccessLevelNamesDataProvider
     *
     * @param array $arguments
     * @param array $levels
     */
    public function testGetAccessLevelNames(array $arguments, array $levels)
    {
        $reflection = new \ReflectionClass('OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipProMetadata');
        /** @var OwnershipProMetadata $metadata */
        $metadata = $reflection->newInstanceArgs($arguments);
        $this->assertEquals($levels, $metadata->getAccessLevelNames());
    }

    /**
     * @return array
     */
    public function getAccessLevelNamesDataProvider()
    {
        return [
            'no owner' => [
                'arguments' => [],
                'levels' => [
                    0 => AccessLevel::NONE_LEVEL_NAME,
                    5 => AccessLevel::getAccessLevelName(AccessLevel::SYSTEM_LEVEL)
                ],
            ],
            'basic level owned' => [
                'arguments' => ['USER', 'owner', 'owner_id'],
                'levels' => [
                    0 => AccessLevel::NONE_LEVEL_NAME,
                    1 => AccessLevel::getAccessLevelName(AccessLevel::BASIC_LEVEL),
                    2 => AccessLevel::getAccessLevelName(AccessLevel::LOCAL_LEVEL),
                    3 => AccessLevel::getAccessLevelName(AccessLevel::DEEP_LEVEL),
                    4 => AccessLevel::getAccessLevelName(AccessLevel::GLOBAL_LEVEL),
                    5 => AccessLevel::getAccessLevelName(AccessLevel::SYSTEM_LEVEL)
                ],
            ],
            'local level owned' => [
                'arguments' => ['BUSINESS_UNIT', 'owner', 'owner_id'],
                'levels' => [
                    0 => AccessLevel::NONE_LEVEL_NAME,
                    2 => AccessLevel::getAccessLevelName(AccessLevel::LOCAL_LEVEL),
                    3 => AccessLevel::getAccessLevelName(AccessLevel::DEEP_LEVEL),
                    4 => AccessLevel::getAccessLevelName(AccessLevel::GLOBAL_LEVEL),
                    5 => AccessLevel::getAccessLevelName(AccessLevel::SYSTEM_LEVEL)
                ],
            ],
            'global level owned' => [
                'arguments' => ['ORGANIZATION', 'owner', 'owner_id'],
                'levels' => [
                    0 => AccessLevel::NONE_LEVEL_NAME,
                    4 => AccessLevel::getAccessLevelName(AccessLevel::GLOBAL_LEVEL),
                    5 => AccessLevel::getAccessLevelName(AccessLevel::SYSTEM_LEVEL)
                ],
            ],
        ];
    }
}
