<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use OroPro\Bundle\OrganizationBundle\Form\Extension\OrganizationListExtension;

class OrganizationListExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrganizationListExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RouterInterface
     */
    protected $router;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Request
     */
    protected $request;

    protected function setUp()
    {
        $this->router = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityContext = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');

        $this->extension = new OrganizationListExtension($this->router, $this->securityFacade, $this->securityContext);

        $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension->setRequest($this->request);
    }

    protected function tearDown()
    {
        unset($this->extension, $this->router, $this->securityFacade, $this->securityContext, $this->request);
    }

    public function testGetExtendedType()
    {
        $this->assertInternalType('string', $this->extension->getExtendedType());
        $this->assertEquals('oro_organization_choice_select2', $this->extension->getExtendedType());
    }

    public function testSetDefaultOptions()
    {
        $host = 'http://example.com';
        $baseUrl = '/app_env.php/base_url';
        $route = '/some/route';
        $url = $host . $baseUrl . $route;
        $routeData = [
            '_controller' => 'SomeBundle\Controller::actionMethod',
            '_route' => 'route_name',
        ];

        $organization = $this->getOrganization(1, 'org-name');
        $organizationWithoutAccess = $this->getOrganization(2, 'no-acl-org');

        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');

        $this->request->expects($this->once())
            ->method('get')
            ->with($this->equalTo('form_url'))
            ->willReturn($url);

        $requestContext = $this->getMockBuilder('Symfony\Component\Routing\RequestContext')
            ->disableOriginalConstructor()
            ->getMock();
        $requestContext->expects($this->any())->method('getBaseUrl')->willReturn($baseUrl);
        $this->router->expects($this->any())->method('getContext')->willReturn($requestContext);
        $this->router->expects($this->once())->method('match')->with($this->equalTo($route))->willReturn($routeData);

        $this->securityFacade->expects($this->once())->method('getClassMethodAnnotation')
            ->willReturn(new Acl(['id' => 'resource_id', 'type' => 'type']));

        $organizations = new ArrayCollection([$organization, $organizationWithoutAccess]);
        $user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $user->expects($this->once())->method('getOrganizations')->willReturn($organizations);
        $this->securityFacade->expects($this->once())->method('getLoggedUser')->willReturn($user);

        $this->securityContext->expects($this->exactly($organizations->count()))
            ->method('isGranted')
            ->with(
                $this->equalTo('resource_id'),
                $this->isInstanceOf('Oro\Bundle\OrganizationBundle\Entity\Organization')
            )
            ->will($this->onConsecutiveCalls([true, false]));

        $resolver->expects($this->once())->method('setDefaults')
            ->with($this->equalTo(['choices' => [1 => 'org-name']]));

        $this->extension->setDefaultOptions($resolver);
    }

    /**
     * @param int $id
     * @param string $name
     * @return \PHPUnit_Framework_MockObject_MockObject|Organization
     */
    protected function getOrganization($id, $name)
    {
        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\Organization');
        $organization->expects($this->any())->method('getId')->willReturn($id);
        $organization->expects($this->any())->method('getName')->willReturn($name);

        return $organization;
    }
}
