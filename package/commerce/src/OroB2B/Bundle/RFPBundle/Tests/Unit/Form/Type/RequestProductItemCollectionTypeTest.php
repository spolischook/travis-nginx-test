<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\RFPBundle\Form\Type\RequestProductItemType;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestProductItemCollectionType;

class RequestProductItemCollectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var RequestProductItemCollectionType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new RequestProductItemCollectionType();
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'type' => RequestProductItemType::NAME,
                'show_form_when_empty'  => false,
                'error_bubbling'        => false,
                'prototype_name'        => '__namerequestproductitem__',
            ])
        ;

        $this->formType->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(CollectionType::NAME, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(RequestProductItemCollectionType::NAME, $this->formType->getName());
    }
}
