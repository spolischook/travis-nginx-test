<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures;

use OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipProMetadata;
use Symfony\Component\DependencyInjection\ContainerInterface;

use OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProProvider;

class OwnershipMetadataProProviderStub extends OwnershipMetadataProProvider
{
    /**
     * @var array
     */
    private $metadata = [];

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
     * @param OwnershipProMetadata $metadata
     */
    public function setMetadata($className, OwnershipProMetadata $metadata)
    {
        $this->metadata[$className] = $metadata;
    }
}
