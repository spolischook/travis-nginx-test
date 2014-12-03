<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Twig;

use OroPro\Bundle\OrganizationBundle\Tests\Unit\Fixture\GlobalOrganization;
use OroPro\Bundle\OrganizationBundle\Twig\OrganizationProExtension;

class OrganizationProExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrganizationProExtension */
    protected $extension;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    public function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new OrganizationProExtension($this->entityManager);
    }

    /**
     * @dataProvider getOrganizationsProvider
     * @param array $testOrganizations
     * @param array $expectedCall
     */
    public function testGetLoginOrganizations($testOrganizations, $expectedCall)
    {
        $repo = $this->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager->expects($this->once())->method('getRepository')->willReturn($repo);
        $repo->expects($this->once())->method('getEnabled')->willReturn($testOrganizations);
        $environment = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $template = $this->getMockBuilder('\Twig_Template')
            ->setMethods(['doDisplay', 'getTemplateName', 'render'])
            ->disableOriginalConstructor()
            ->getMock();
        $environment->expects($this->once())->method('loadTemplate')->willReturn($template);

        $fieldName = 'organization';
        $label = 'orgLabel';
        $showLabels = true;
        $template->expects($this->once())->method('render')->with(
            [
                'organizations' => $expectedCall,
                'fieldName' => $fieldName,
                'label' => $label,
                'showLabels' => $showLabels
            ]
        );

        $this->extension->getLoginOrganizations($environment, $fieldName, $label, $showLabels);
    }

    public function getOrganizationsProvider()
    {
        $globalOrg = new GlobalOrganization();
        $globalOrg->setName('global');
        $globalOrg->setId(85);
        $globalOrg->setIsGlobal(true);

        $firstOrg = new GlobalOrganization();
        $firstOrg->setName('first');
        $firstOrg->setId(1);
        $firstOrg->setIsGlobal(false);

        $secondOrg = new GlobalOrganization();
        $secondOrg->setName('second');
        $secondOrg->setId(55);
        $secondOrg->setIsGlobal(false);

        return [
            [
                [$globalOrg, $firstOrg,$secondOrg],
                [
                    ['id' => 85, 'name' => 'global'],
                    ['id' => 1, 'name' => '&nbsp;&nbsp;&nbsp;first'],
                    ['id' => 55, 'name' => '&nbsp;&nbsp;&nbsp;second'],
                ]
            ],
            [
                [$firstOrg, $secondOrg],
                [
                    ['id' => 1, 'name' => 'first'],
                    ['id' => 55, 'name' => 'second'],
                ]
            ],
        ];
    }
}
