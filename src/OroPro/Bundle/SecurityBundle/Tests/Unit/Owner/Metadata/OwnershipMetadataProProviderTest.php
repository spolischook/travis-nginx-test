<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

use OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProProvider;
use OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipProMetadata;

class OwnershipMetadataProProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testOwnerClassesConfig()
    {
        $entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $entityClassResolver->expects($this->exactly(3))
            ->method('getEntityClass')
            ->will(
                $this->returnValueMap(
                    array(
                        array('AcmeBundle:Organization', 'AcmeBundle\Entity\Organization'),
                        array('AcmeBundle:BusinessUnit', 'AcmeBundle\Entity\BusinessUnit'),
                        array('AcmeBundle:User', 'AcmeBundle\Entity\User'),
                    )
                )
            );

        $provider = new OwnershipMetadataProProvider(
            array(
                'organization' => 'AcmeBundle:Organization',
                'business_unit' => 'AcmeBundle:BusinessUnit',
                'user' => 'AcmeBundle:User',
            ),
            $configProvider,
            $entityClassResolver
        );

        $this->assertEquals('AcmeBundle\Entity\Organization', $provider->getOrganizationClass());
        $this->assertEquals('AcmeBundle\Entity\BusinessUnit', $provider->getBusinessUnitClass());
        $this->assertEquals('AcmeBundle\Entity\User', $provider->getUserClass());
    }

    public function testGetMetadataUndefinedClassWithoutCache()
    {
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $provider = new OwnershipMetadataProProvider(
            array(
                'organization' => 'AcmeBundle\Entity\Organization',
                'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                'user' => 'AcmeBundle\Entity\User',
            ),
            $configProvider,
            null
        );

        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($this->equalTo('UndefinedClass'))
            ->will($this->returnValue(false));

        $configProvider->expects($this->never())
            ->method('getConfig');

        $this->assertEquals(
            new OwnershipProMetadata(),
            $provider->getMetadata('UndefinedClass')
        );
    }

    public function testGetMetadataWithoutCache()
    {
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $provider = new OwnershipMetadataProProvider(
            array(
                'organization' => 'AcmeBundle\Entity\Organization',
                'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                'user' => 'AcmeBundle\Entity\User',
            ),
            $configProvider,
            null
        );

        $config = new Config(new EntityConfigId('ownership', 'SomeClass'));
        $config->set('owner_type', 'USER');
        $config->set('owner_field_name', 'test_field');
        $config->set('owner_column_name', 'test_column');

        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($this->equalTo('SomeClass'))
            ->will($this->returnValue(true));

        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($this->equalTo('SomeClass'))
            ->will($this->returnValue($config));

        $this->assertEquals(
            new OwnershipProMetadata('USER', 'test_field', 'test_column'),
            $provider->getMetadata('SomeClass')
        );
    }

    public function testGetMetadataSetsOrganizationFieldName()
    {
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $provider = new OwnershipMetadataProProvider(
            array(
                'organization' => 'AcmeBundle\Entity\Organization',
                'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                'user' => 'AcmeBundle\Entity\User',
            ),
            $configProvider,
            null
        );

        $config = new Config(new EntityConfigId('ownership', 'SomeClass'));
        $config->set('owner_type', 'ORGANIZATION');
        $config->set('owner_field_name', 'test_field');
        $config->set('owner_column_name', 'test_column');

        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($this->equalTo('SomeClass'))
            ->will($this->returnValue(true));

        $configProvider->expects($this->once())
            ->method('getConfig')
            ->with($this->equalTo('SomeClass'))
            ->will($this->returnValue($config));

        $this->assertEquals(
            new OwnershipProMetadata('ORGANIZATION', 'test_field', 'test_column', 'test_field', 'test_column'),
            $provider->getMetadata('SomeClass')
        );
    }

    public function testGetMetadataUndefinedClassWithCache()
    {
        $configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $cache = $this->getMockForAbstractClass(
            'Doctrine\Common\Cache\CacheProvider',
            array(),
            '',
            false,
            true,
            true,
            array('fetch', 'save')
        );

        $provider = new OwnershipMetadataProProvider(
            array(
                'organization' => 'AcmeBundle\Entity\Organization',
                'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                'user' => 'AcmeBundle\Entity\User',
            ),
            $configProvider,
            null,
            $cache
        );

        $metadata = new OwnershipProMetadata();

        $configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($this->equalTo('UndefinedClass'))
            ->will($this->returnValue(false));

        $configProvider->expects($this->never())
            ->method('getConfig');

        $cache->expects($this->at(0))
            ->method('fetch')
            ->with($this->equalTo('UndefinedClass'))
            ->will($this->returnValue(false));
        $cache->expects($this->at(2))
            ->method('fetch')
            ->with($this->equalTo('UndefinedClass'))
            ->will($this->returnValue(true));
        $cache->expects($this->once())
            ->method('save')
            ->with($this->equalTo('UndefinedClass'), $this->equalTo(true));

        // no cache
        $this->assertEquals(
            $metadata,
            $provider->getMetadata('UndefinedClass')
        );

        // local cache
        $this->assertEquals(
            $metadata,
            $provider->getMetadata('UndefinedClass')
        );

        // cache
        $provider = new OwnershipMetadataProProvider(
            array(
                'organization' => 'AcmeBundle\Entity\Organization',
                'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                'user' => 'AcmeBundle\Entity\User',
            ),
            $configProvider,
            null,
            $cache
        );
        $this->assertEquals(
            $metadata,
            $provider->getMetadata('UndefinedClass')
        );
    }
}
