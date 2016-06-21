<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Twig;

use OroPro\Bundle\OrganizationBundle\Twig\OrganizationExtension;

class OrganizationExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $organizationProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $organizationHelper;

    /** @var OrganizationExtension */
    protected $extension;

    public function setUp()
    {
        $this->doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->organizationProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->organizationHelper =
            $this->getMockBuilder('OroPro\Bundle\OrganizationBundle\Helper\OrganizationProHelper')
                ->disableOriginalConstructor()
                ->getMock();

        $this->extension = new OrganizationExtension(
            $this->organizationProvider,
            $this->doctrine,
            $this->translator,
            $this->organizationHelper
        );
    }

    public function testGetFunctions()
    {
        $result = $this->extension->getFunctions();
        $this->assertCount(2, $result);
        $this->assertEquals('oropro_applicable_organizations', $result[0]->getName());
        $this->assertEquals('oropro_global_org_id', $result[1]->getName());
    }

    public function testGetGlobalOrgId()
    {
        $this->organizationHelper->expects($this->once())->method('getGlobalOrganizationId')->willReturn(1);
        $this->assertEquals(1, $this->extension->getGlobalOrgId());
    }
}
