<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Form\EventListener;

use OroPro\Bundle\OrganizationBundle\Form\EventListener\OrganizationFieldListener;
use OroPro\Bundle\OrganizationBundle\Tests\Unit\Fixture\GlobalOrganization;

class OrganizationFieldListenerTest extends \PHPUnit_Framework_TestCase
{
    const ROLE_CLASS_NAME = 'use Oro\Bundle\UserBundle\Entity\Role';

    public function testAddOrganizationFieldWhenOrganizationIsGlobal()
    {
        $env      = $this->getMockBuilder('Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
        $newField = "<input>";
        $env->expects($this->once())->method('render')->will($this->returnValue($newField));
        $formView        = $this->getMockBuilder('Symfony\Component\Form\FormView')
            ->disableOriginalConstructor()
            ->getMock();

        $formView->vars = [
            'value' => self::ROLE_CLASS_NAME,
            'attr' => []
        ];

        $currentFormData = 'someHTML';
        $formData        = [
            'dataBlocks' => [
                [
                    'subblocks' => [
                        ['data' => [$currentFormData]]
                    ]
                ]
            ]
        ];

        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())->method('getTwigEnvironment')->will($this->returnValue($env));
        $event->expects($this->once())->method('getFormData')->will($this->returnValue($formData));
        $event->expects($this->once())->method('getForm')->will($this->returnValue($formView));

        array_unshift($formData['dataBlocks'][0]['subblocks'][0]['data'], $newField);
        $event->expects($this->once())->method('setFormData')->with($formData);

        $provider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $provider->expects($this->any())
            ->method('hasConfig')
            ->will($this->returnValue(false));
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->any())
            ->method('getProvider')
            ->will($this->returnValue($provider));

        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $organization = new GlobalOrganization();

        $securityFacade->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $listener = new OrganizationFieldListener($configManager, $securityFacade);

        $listener->addOrganizationField($event);
    }

    public function testAddOrganizationFieldWhenOrganizationIsNoGlobal()
    {
        $event = $this->getMockBuilder('Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->never())->method('getTwigEnvironment');

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->never())->method($this->anything());

        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $organization = new GlobalOrganization();
        $organization->setIsGlobal(false);

        $securityFacade->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $event->expects($this->never())->method($this->anything());

        $listener = new OrganizationFieldListener($configManager, $securityFacade);

        $listener->addOrganizationField($event);
    }
}
