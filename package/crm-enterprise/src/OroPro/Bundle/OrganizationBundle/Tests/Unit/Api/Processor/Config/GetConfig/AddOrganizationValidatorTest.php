<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Api\Processor\Config\GetConfig;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\ConfigProcessorTestCase;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use OroPro\Bundle\OrganizationBundle\Api\Processor\Config\GetConfig\AddOrganizationValidator;

class AddOrganizationValidatorTest extends ConfigProcessorTestCase
{
    /** @var AddOrganizationValidator */
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

        $this->processor = new AddOrganizationValidator($this->doctrineHelper, $this->ownershipMetadataProvider);
    }

    public function testProcessOnNonManageableEntity()
    {
        $className = 'stdClass';
        $this->context->setClassName($className);
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with($className)
            ->willReturn(false);
        $this->ownershipMetadataProvider->expects($this->never())
            ->method('getMetadata');

        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $className = 'stdClass';

        $fieldConfig = new EntityDefinitionFieldConfig();

        $definition = new EntityDefinitionConfig();
        $definition->addField('owner', $fieldConfig);
        $definition->addField('org', $fieldConfig);

        $this->context->setClassName($className);
        $this->context->setResult($definition);

        $ownershipMetadata = new OwnershipMetadata('USER', 'owner', 'owner', 'org', 'org');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with($className)
            ->willReturn(true);

        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with($className)
            ->willReturn($ownershipMetadata);

        $this->processor->process($this->context);

        $formOptions = $fieldConfig->getFormOptions();
        $this->assertEquals(1, count($formOptions));
        $this->assertInstanceOf('Symfony\Component\Validator\Constraints\NotBlank', $formOptions['constraints'][0]);
        $entityFormOptions = $definition->getFormOptions();
        $this->assertEquals(1, count($entityFormOptions));
        $this->assertInstanceOf(
            'OroPro\Bundle\OrganizationBundle\Validator\Constraints\Organization',
            $entityFormOptions['constraints'][0]
        );
    }

    public function testProcessWithoutOwnerField()
    {
        $className = 'stdClass';

        $fieldConfig = new EntityDefinitionFieldConfig();

        $definition = new EntityDefinitionConfig();
        $definition->addField('nonowner', $fieldConfig);

        $this->context->setClassName($className);
        $this->context->setResult($definition);

        $ownershipMetadata = new OwnershipMetadata('USER', 'owner', 'owner', 'org', 'org');

        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with($className)
            ->willReturn(true);

        $this->ownershipMetadataProvider->expects($this->once())
            ->method('getMetadata')
            ->with($className)
            ->willReturn($ownershipMetadata);

        $this->processor->process($this->context);

        $this->assertEmpty($fieldConfig->getFormOptions());
        $this->assertEmpty($definition->getFormOptions());
    }
}
