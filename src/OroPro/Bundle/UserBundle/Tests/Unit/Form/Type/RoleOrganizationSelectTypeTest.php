<?php

namespace OroPro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\UserBundle\Entity\User;
use OroPro\Bundle\UserBundle\Form\Type\RoleOrganizationSelectType;
use OroPro\Bundle\UserBundle\Helper\UserProHelper;

class RoleOrganizationSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RoleOrganizationSelectType
     */
    protected $roleOrganizationSelectType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UserProHelper
     */
    protected $userHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface
     */
    protected $token;

    protected function setUp()
    {
        $this->securityContext = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        $this->userHelper = $this->getMockBuilder('OroPro\Bundle\UserBundle\Helper\UserProHelper')
            ->getMock();

        $this->token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $this->roleOrganizationSelectType = new RoleOrganizationSelectType($this->securityContext, $this->userHelper);
    }

    protected function tearDown()
    {
        unset(
            $this->securityContext,
            $this->userHelper,
            $this->token,
            $this->roleOrganizationSelectType
        );
    }

    public function testSetDefaultOptionsWhenUserIsAssignedToSystemOrganization()
    {
        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($this->token));

        $user = new User();
        $this->token->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $this->userHelper->expects($this->once())
            ->method('isUserAssignedToSystemOrganization')
            ->will($this->returnValue(true));

        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');

        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->roleOrganizationSelectType->setDefaultOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_jqueryselect2_hidden', $this->roleOrganizationSelectType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oropro_user_role_organization_select', $this->roleOrganizationSelectType->getName());
    }
}
