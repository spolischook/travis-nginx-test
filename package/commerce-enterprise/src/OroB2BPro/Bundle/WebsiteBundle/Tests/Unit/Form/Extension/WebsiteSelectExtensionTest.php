<?php

namespace OroB2BPro\Bundle\WebsiteBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

use OroB2BPro\Bundle\WebsiteBundle\Form\Extension\WebsiteSelectExtension;

class WebsiteSelectExtensionTest extends \PHPUnit_Framework_TestCase
{
    const WEBSITE_LABEL = 'website.label';
    const EXTENDED_TYPE = 'extended.type';

    /**
     * @var WebsiteSelectExtension
     */
    protected $websiteSelectExtension;

    protected function setUp()
    {
        $this->websiteSelectExtension = new WebsiteSelectExtension();
        $this->websiteSelectExtension->setLabel(self::WEBSITE_LABEL);
        $this->websiteSelectExtension->setExtendedType(self::EXTENDED_TYPE);
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder * */
        $builder = $this->getMock(FormBuilderInterface::class);

        $builder->expects($this->once())->method('add')->with(
            'website',
            'entity',
            [
                'class' => 'OroB2B\Bundle\WebsiteBundle\Entity\Website',
                'label' => self::WEBSITE_LABEL,
            ]
        );
        $this->websiteSelectExtension->buildForm($builder, []);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(self::EXTENDED_TYPE, $this->websiteSelectExtension->getExtendedType());
    }
}
