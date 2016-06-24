<?php

namespace OroB2BPro\Bundle\WebsiteBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

use OroB2BPro\Bundle\WebsiteBundle\Form\Extension\WebsiteSelectExtension;

class WebsiteSelectExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebsiteSelectExtension
     */
    protected $websiteSelectExtension;

    public function testBuildForm()
    {
        $this->websiteSelectExtension = new WebsiteSelectExtension();
        $label='website.label';
        $this->websiteSelectExtension->setLabel($label);
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder * */
        $builder = $this->getMock(FormBuilderInterface::class);

        $builder->expects($this->once())->method('add')->with(
            'website',
            'entity',
            [
                'class' => 'OroB2B\Bundle\WebsiteBundle\Entity\Website',
                'label' => $label,
            ]
        );
        $this->websiteSelectExtension->buildForm($builder, []);
    }

    public function testGetExtendedType()
    {
        $this->websiteSelectExtension = new WebsiteSelectExtension();
        $type='extended.type';
        $this->websiteSelectExtension->setExtendedType($type);
        $this->assertEquals($type, $this->websiteSelectExtension->getExtendedType());
    }
}
