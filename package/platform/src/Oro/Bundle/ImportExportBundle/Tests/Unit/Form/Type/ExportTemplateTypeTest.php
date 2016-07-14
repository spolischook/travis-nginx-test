<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\ImportExportBundle\Form\Type\ExportTemplateType;

class ExportTemplateTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProcessorRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $processorRegistry;

    /**
     * @var ExportTemplateType
     */
    protected $exportTemplateType;

    protected function setUp()
    {
        parent::setUp();

        $this->processorRegistry = $this->getMockBuilder(ProcessorRegistry::class)->getMock();
        $this->exportTemplateType = new ExportTemplateType($this->processorRegistry);
    }

    public function testBuildFormShouldAddEventListener()
    {
        $builder = $this->getBuilderMock();
        $this->processorRegistry->expects($this->once())
            ->method('getProcessorAliasesByEntity')
            ->willReturn([]);

        $builder->expects($this->once())
            ->method('addEventListener');

        $this->exportTemplateType->buildForm($builder, ['entityName' => 'xxx']);
    }

    public function testBuildFormShouldCreateCorrectChoices()
    {
        $builder = $this->getBuilderMock();
        $phpunitTestCase = $this;
        $this->processorRegistry->expects($this->once())
            ->method('getProcessorAliasesByEntity')
            ->willReturn(['testProcess1', 'testProcess2']);

        $builder->expects($this->once())
            ->method('add')
            ->will($this->returnCallback(function ($name, $type, $options) use ($phpunitTestCase) {
                $choices = $options['choices'];
                $phpunitTestCase->assertArrayHasKey(
                    'testProcess1',
                    $choices
                );
                $phpunitTestCase->assertArrayHasKey(
                    'testProcess2',
                    $choices
                );
            }));

        $this->exportTemplateType->buildForm($builder, ['entityName' => 'xxx']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface
     */
    protected function getBuilderMock()
    {
        return $this->getMockBuilder(FormBuilderInterface::class)->getMock();
    }
}
