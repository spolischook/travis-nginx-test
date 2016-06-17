<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;

use OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipProMetadata;
use OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProProvider;

class OwnershipMetadataProProviderStub extends OwnershipMetadataProProvider
{
    /**
     * @var array
     */
    private $metadata = [];

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityContextInterface
     */
    private $securityContext;

    /**
     * @param \PHPUnit_Framework_TestCase $testCase
     */
    public function __construct(\PHPUnit_Framework_TestCase $testCase)
    {
        $configProvider = $testCase->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $entityClassResolver = $testCase->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $entityClassResolver->expects($testCase->any())->method('getEntityClass')->willReturnArgument(0);

        $this->securityContext = $testCase
            ->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
            ->disableOriginalConstructor()
            ->getMock();

        $container = $testCase->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($testCase->any())
            ->method('get')
            ->will(
                $testCase->returnValueMap(
                    [
                        [
                            'oro_entity_config.provider.ownership',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $configProvider,
                        ],
                        [
                            'oro_entity.orm.entity_class_resolver',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $entityClassResolver,
                        ],
                        [
                            'security.context',
                            ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
                            $this->securityContext,
                        ],
                    ]
                )
            );

        parent::__construct(
            [
                'organization' => 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\Organization',
                'business_unit' => 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\BusinessUnit',
                'user' => 'Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User',
            ]
        );

        $this->setContainer($container);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($className)
    {
        return isset($this->metadata[$className])
            ? $this->metadata[$className]
            : new OwnershipProMetadata();
    }

    /**
     * @param string $className
     * @param OwnershipMetadata $metadata
     */
    public function setMetadata($className, OwnershipMetadata $metadata)
    {
        $this->metadata[$className] = $metadata;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SecurityContextInterface
     */
    public function getSecurityContext()
    {
        return $this->securityContext;
    }
}
