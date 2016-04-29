<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Api\Processor\Config\GetConfig;

use Symfony\Component\Validator\Constraints\NotNull;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use OroPro\Bundle\OrganizationBundle\Api\Processor\Config\GetConfig\AddOrganizationNotNullValidator;

class AddOrganizationNotNullValidatorTest extends ConfigProcessorTestCase
{
    /** @var AddOrganizationNotNullValidator */
    protected $processor;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $ownershipMetadataProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->ownershipMetadataProvider = $this
            ->getMockBuilder('OroPro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new AddOrganizationNotNullValidator($this->doctrineHelper, $this->ownershipMetadataProvider);
    }

    public function testProcessForNonManageableEntity()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(false);
        $this->ownershipMetadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $config = [
            'fields' => [
                'owner' => null,
                'org'   => null,
            ]
        ];
        $ownershipMetadata = new OwnershipMetadata('USER', 'owner', 'owner', 'org', 'org');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn(true);
        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with(self::TEST_CLASS_NAME)
            ->willReturn($ownershipMetadata);

        /** @var EntityDefinitionConfig $configObject */
        $configObject = $this->createConfigObject($config);
        $this->context->setResult($configObject);
        $this->processor->process($this->context);

        $this->assertEquals(
            ['constraints' => [new NotNull()]],
            $configObject->getField('org')->getFormOptions()
        );
    }
}
