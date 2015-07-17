<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use OroPro\Bundle\SecurityBundle\Tests\Unit\Fixture\GlobalOrganization;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

use OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProProvider;
use OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipProMetadata;

class OwnershipMetadataProProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface
     */
    protected $container;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider
     */
    protected $configProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EntityClassResolver
     */
    protected $entityClassResolver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityContextInterface
     */
    protected $securityContext;

    protected function setUp()
    {
        $this->entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityContext = $this
            ->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->will(
                $this->returnValueMap(
                    [
                        ['AcmeBundle:Organization', 'AcmeBundle\Entity\Organization'],
                        ['AcmeBundle:BusinessUnit', 'AcmeBundle\Entity\BusinessUnit'],
                        ['AcmeBundle:User', 'AcmeBundle\Entity\User'],
                    ]
                )
            );

        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'oro_entity_config.provider.ownership',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->configProvider,
                        ],
                        [
                            'oro_entity.orm.entity_class_resolver',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->entityClassResolver,
                        ],
                        [
                            'security.context',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->securityContext,
                        ],
                    ]
                )
            );
    }

    protected function tearDown()
    {
        unset($this->configProvider, $this->container);
    }

    public function testOwnerClassesConfig()
    {

        $provider = new OwnershipMetadataProProvider(
            [
                'organization' => 'AcmeBundle:Organization',
                'business_unit' => 'AcmeBundle:BusinessUnit',
                'user' => 'AcmeBundle:User',
            ],
            $this->configProvider
        );
        $provider->setContainer($this->container);

        $this->assertEquals('AcmeBundle\Entity\Organization', $provider->getOrganizationClass());
        $this->assertEquals('AcmeBundle\Entity\Organization', $provider->getGlobalLevelClass());
        $this->assertEquals('AcmeBundle\Entity\BusinessUnit', $provider->getBusinessUnitClass());
        $this->assertEquals('AcmeBundle\Entity\BusinessUnit', $provider->getLocalLevelClass());
        $this->assertEquals('AcmeBundle\Entity\User', $provider->getUserClass());
        $this->assertEquals('AcmeBundle\Entity\User', $provider->getBasicLevelClass());
    }

    public function testGetMetadataUndefinedClassWithoutCache()
    {
        $provider = new OwnershipMetadataProProvider(
            [
                'organization' => 'AcmeBundle\Entity\Organization',
                'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                'user' => 'AcmeBundle\Entity\User',
            ],
            $this->configProvider
        );
        $provider->setContainer($this->container);

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($this->equalTo('UndefinedClass'))
            ->will($this->returnValue(false));

        $this->configProvider->expects($this->never())
            ->method('getConfig');

        $this->assertEquals(
            new OwnershipProMetadata(),
            $provider->getMetadata('UndefinedClass')
        );
    }

    public function testGetMetadataWithoutCache()
    {
        $provider = new OwnershipMetadataProProvider(
            [
                'organization' => 'AcmeBundle\Entity\Organization',
                'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                'user' => 'AcmeBundle\Entity\User',
            ],
            $this->configProvider
        );
        $provider->setContainer($this->container);

        $config = new Config(new EntityConfigId('ownership', 'SomeClass'));
        $config->set('owner_type', 'USER');
        $config->set('owner_field_name', 'test_field');
        $config->set('owner_column_name', 'test_column');

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($this->equalTo('SomeClass'))
            ->will($this->returnValue(true));

        $this->configProvider->expects($this->once())
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
        $provider = new OwnershipMetadataProProvider(
            [
                'organization' => 'AcmeBundle\Entity\Organization',
                'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                'user' => 'AcmeBundle\Entity\User',
            ],
            $this->configProvider
        );
        $provider->setContainer($this->container);

        $config = new Config(new EntityConfigId('ownership', 'SomeClass'));
        $config->set('owner_type', 'ORGANIZATION');
        $config->set('owner_field_name', 'test_field');
        $config->set('owner_column_name', 'test_column');

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($this->equalTo('SomeClass'))
            ->will($this->returnValue(true));

        $this->configProvider->expects($this->once())
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
        $cache = $this->getMockForAbstractClass(
            'Doctrine\Common\Cache\CacheProvider',
            [],
            '',
            false,
            true,
            true,
            ['fetch', 'save']
        );

        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'oro_entity_config.provider.ownership',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->configProvider,
                        ],
                        [
                            'oro_entity.orm.entity_class_resolver',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->entityClassResolver,
                        ],
                        [
                            'oro_security.owner.ownership_metadata_provider.cache',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $cache,
                        ],
                    ]
                )
            );

        $provider = new OwnershipMetadataProProvider(
            [
                'organization' => 'AcmeBundle\Entity\Organization',
                'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                'user' => 'AcmeBundle\Entity\User',
            ],
            $this->configProvider
        );
        $provider->setContainer($this->container);

        $metadata = new OwnershipProMetadata();

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($this->equalTo('UndefinedClass'))
            ->will($this->returnValue(false));

        $this->configProvider->expects($this->never())
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
            [
                'organization' => 'AcmeBundle\Entity\Organization',
                'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                'user' => 'AcmeBundle\Entity\User',
            ],
            $this->configProvider,
            null,
            $cache
        );
        $provider->setContainer($this->container);

        $this->assertEquals(
            $metadata,
            $provider->getMetadata('UndefinedClass')
        );
    }

    public function testGetMaxAccessLevelInGlobalMode()
    {
        $provider = new OwnershipMetadataProProvider(
            [
                'organization' => 'AcmeBundle\Entity\Organization',
                'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                'user' => 'AcmeBundle\Entity\User',
            ],
            $this->configProvider
        );
        $provider->setContainer($this->container);

        $organization = new GlobalOrganization();
        $token = new UsernamePasswordOrganizationToken('admin', 'admin', 'key', $organization);
        $this->securityContext->expects($this->once())->method('getToken')->willReturn($token);

        $this->assertEquals(AccessLevel::SYSTEM_LEVEL, $provider->getMaxAccessLevel(AccessLevel::SYSTEM_LEVEL));
    }

    public function testGetMaxAccessLevelNotInGlobalMode()
    {
        $provider = new OwnershipMetadataProProvider(
            [
                'organization' => 'AcmeBundle\Entity\Organization',
                'business_unit' => 'AcmeBundle\Entity\BusinessUnit',
                'user' => 'AcmeBundle\Entity\User',
            ],
            $this->configProvider
        );
        $provider->setContainer($this->container);

        $config = new Config(new EntityConfigId('ownership', '\stdClass'));
        $config->set('owner_type', 'USER');
        $config->set('owner_field_name', 'test_field');
        $config->set('owner_column_name', 'test_column');

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($this->equalTo('\stdClass'))
            ->will($this->returnValue(true));

        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($this->equalTo('\stdClass'))
            ->will($this->returnValue($config));

        $this->securityContext->expects($this->once())->method('getToken')->willReturn(null);

        $this->assertEquals(
            AccessLevel::GLOBAL_LEVEL,
            $provider->getMaxAccessLevel(AccessLevel::SYSTEM_LEVEL, '\stdClass')
        );
    }
}
