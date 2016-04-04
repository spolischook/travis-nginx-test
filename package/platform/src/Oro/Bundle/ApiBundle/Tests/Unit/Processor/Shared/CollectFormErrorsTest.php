<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Shared\CollectFormErrors;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class CollectFormErrorsTest extends FormProcessorTestCase
{
    /** @var CollectFormErrors */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->processor = new CollectFormErrors();
    }

    public function testProcessWithoutForm()
    {
        $this->processor->process($this->context);
        $this->assertFalse($this->context->hasErrors());
    }

    public function testProcessWithNotSubmittedForm()
    {
        $form = $this->createFormBuilder()->create('testForm')->getForm();

        $this->context->setForm($form);
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasErrors());
    }

    public function testProcessWithoutFormConstraints()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add('field1', 'text')
            ->add('field2', 'text')
            ->getForm();
        $form->submit([]);

        $this->context->setForm($form);
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasErrors());
    }

    public function testProcessWithEmptyData()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add('field1', 'text', ['constraints' => [new Constraints\NotBlank()]])
            ->add('field2', 'text', ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();
        $form->submit([]);

        $this->context->setForm($form);
        $this->processor->process($this->context);

        $this->assertFalse($form->isValid());
        $this->assertTrue($this->context->hasErrors());
        $this->assertEquals(
            [
                $this->createErrorObject('This value should not be blank.', 'field1'),
                $this->createErrorObject('This value should not be blank.', 'field2')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidDataKey()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add('field1', 'text', ['constraints' => [new Constraints\NotBlank()]])
            ->add('field2', 'text', ['constraints' => [new Constraints\NotBlank()]])
            ->getForm();
        $form->submit(
            [
                'field1' => 'value',
                'field3' => 'value'
            ]
        );

        $this->context->setForm($form);
        $this->processor->process($this->context);

        $this->assertFalse($form->isValid());
        $this->assertTrue($this->context->hasErrors());
        $this->assertEquals(
            [
                $this->createErrorObject('This form should not contain extra fields.', 'testForm'),
                $this->createErrorObject('This value should not be blank.', 'field2')
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWithInvalidValues()
    {
        $form = $this->createFormBuilder()->create('testForm', null, ['compound' => true])
            ->add('field1', 'text', ['constraints' => [new Constraints\NotBlank(), new Constraints\NotNull()]])
            ->add('field2', 'text', ['constraints' => [new Constraints\Length(['min' => 2, 'max' => 4])]])
            ->getForm();
        $form->submit(
            [
                'field1' => null,
                'field2' => 'value'
            ]
        );

        $this->context->setForm($form);
        $this->processor->process($this->context);

        $this->assertFalse($form->isValid());
        $this->assertTrue($this->context->hasErrors());
        $this->assertEquals(
            [
                $this->createErrorObject('This value should not be blank.', 'field1'),
                $this->createErrorObject('This value should not be null.', 'field1'),
                $this->createErrorObject('This value is too long. It should have 4 characters or less.', 'field2')
            ],
            $this->context->getErrors()
        );
    }

    /**
     * @return FormBuilder
     */
    protected function createFormBuilder()
    {
        $formFactory = Forms::createFormFactoryBuilder()
            ->addExtensions(
                [
                    new ValidatorExtension(Validation::createValidator())
                ]
            )
            ->getFormFactory();
        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        return new FormBuilder(null, null, $dispatcher, $formFactory);
    }

    /**
     * @param string $errorMessage
     * @param string $propertyPath
     *
     * @return Error
     */
    protected function createErrorObject($errorMessage, $propertyPath)
    {
        $error = new Error();
        $error->setDetail($errorMessage);
        $error->setPropertyName($propertyPath);

        return $error;
    }
}
