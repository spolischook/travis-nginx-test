<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Api\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
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
        $className = 'stdClass';
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with($className)
            ->willReturn(false);
        $this->ownershipMetadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->context->setClassName($className);
        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $className = 'stdClass';
        $fieldConfig = new EntityDefinitionFieldConfig();
        $definition = new EntityDefinitionConfig();
        $definition->addField('owner', $fieldConfig);
        $definition->addField('org', $fieldConfig);
        $ownershipMetadata = new OwnershipMetadata('USER', 'owner', 'owner', 'org', 'org');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with($className)
            ->willReturn(true);

        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with($className)
            ->willReturn($ownershipMetadata);

        $this->context->setClassName($className);
        $this->context->setResult($definition);
        $this->processor->process($this->context);

        $formOptions = $fieldConfig->getFormOptions();
        $this->assertEquals(1, count($formOptions));
        $this->assertInstanceOf('Symfony\Component\Validator\Constraints\NotNull', $formOptions['constraints'][0]);
    }

    public function testProcessWithoutOwnerField()
    {
        $className = 'stdClass';
        $fieldConfig = new EntityDefinitionFieldConfig();

        $definition = new EntityDefinitionConfig();
        $definition->addField('nonowner', $fieldConfig);

        $ownershipMetadata = new OwnershipMetadata('USER', 'owner', 'owner', 'org', 'org');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with($className)
            ->willReturn(true);
        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with($className)
            ->willReturn($ownershipMetadata);

        $this->context->setClassName($className);
        $this->context->setResult($definition);
        $this->processor->process($this->context);
        $this->assertEmpty($fieldConfig->getFormOptions());
    }
}
