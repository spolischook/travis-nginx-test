<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata;

use OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipProMetadata;

class OwnershipProMetadataTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorWithoutParameters()
    {
        $metadata = new OwnershipProMetadata();
        $this->assertFalse($metadata->hasOwner());
        $this->assertFalse($metadata->isOrganizationOwned());
        $this->assertFalse($metadata->isBusinessUnitOwned());
        $this->assertFalse($metadata->isUserOwned());
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
        $this->assertFalse($metadata->isBusinessUnitOwned());
        $this->assertFalse($metadata->isUserOwned());
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
        $this->assertTrue($metadata->isBusinessUnitOwned());
        $this->assertFalse($metadata->isUserOwned());
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
        $this->assertFalse($metadata->isBusinessUnitOwned());
        $this->assertTrue($metadata->isUserOwned());
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
        $this->assertEquals('', $metadata->getOwnerFieldName());
        $this->assertEquals('', $metadata->getOwnerColumnName());
        $metadata = unserialize($data);
        $this->assertTrue($metadata->isOrganizationOwned());
        $this->assertEquals('org', $metadata->getOwnerFieldName());
        $this->assertEquals('org_id', $metadata->getOwnerColumnName());
        $this->assertFalse($metadata->isGlobalView());
    }
}
