<?php

namespace OroPro\Bundle\UserBundle\Tests\Unit\Autocomplete;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\OrganizationBundle\Autocomplete\OrganizationSearchHandler as BaseOrganizationSearchHandler;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

use OroPro\Bundle\UserBundle\Autocomplete\RoleOrganizationSearchHandler;

class RoleOrganizationSearchHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RoleOrganizationSearchHandler
     */
    protected $handler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|BaseOrganizationSearchHandler
     */
    protected $baseHandler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|OrganizationContextTokenInterface
     */
    protected $token;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Organization
     */
    protected $organization;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Organization
     */
    protected $organizationHelper;

    protected function setUp()
    {
        $this->baseHandler = $this
            ->getMockBuilder('Oro\Bundle\OrganizationBundle\Autocomplete\OrganizationSearchHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenStorage = $this->getMock(
            'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'
        );

        $this->token = $this->getMock(
            'Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface'
        );

        $this->organization = $this->getMock(
            'Oro\Bundle\OrganizationBundle\Entity\Organization',
            ['getIsGlobal', 'getId']
        );

        $this->organizationHelper = $this
            ->getMockBuilder('OroPro\Bundle\OrganizationBundle\Helper\OrganizationProHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new RoleOrganizationSearchHandler(
            $this->baseHandler,
            $this->tokenStorage,
            $this->organizationHelper
        );
    }

    public function testSearchIfUserLoggedInToGlobalOrganization()
    {
        $expectedSearchResults = ['results' => [['id' => 1], ['id' => 2]]];
        $this->baseHandler->expects($this->once())->method('search')
            ->with('org', 1, 10)
            ->will($this->returnValue($expectedSearchResults));

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($this->token));

        $this->token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($this->organization));

        $this->organization->expects($this->once())
            ->method('getIsGlobal')
            ->will($this->returnValue(true));

        $this->organizationHelper->expects($this->never())
            ->method('isGlobalOrganizationExists');

        $searchResults = $this->handler->search('org', 1, 10);

        $this->assertEquals($expectedSearchResults, $searchResults);
    }

    public function testSearchIfUserNotLoggedInToGlobalOrganization()
    {
        $expectedSearchResults = ['results' => [['id' => 2]]];
        $baseSearchResults = ['results' => [['id' => 1], ['id' => 2]]];
        $this->baseHandler->expects($this->once())->method('search')
            ->with('org', 1, 10)
            ->will($this->returnValue($baseSearchResults));

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($this->token));

        $this->token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($this->organization));

        $this->organization->expects($this->once())
            ->method('getIsGlobal')
            ->will($this->returnValue(false));

        $this->organization->expects($this->atLeastOnce())
            ->method('getId')
            ->will($this->returnValue(2));

        $this->organizationHelper->expects($this->once())
            ->method('isGlobalOrganizationExists')
            ->willReturn(true);

        $searchResults = $this->handler->search('org', 1, 10);

        $this->assertEquals($expectedSearchResults, $searchResults);
    }

    public function testSearchIfGlobalOrganizationDoesNotExists()
    {
        $expectedSearchResults = ['results' => [['id' => 1], ['id' => 2]]];
        $this->baseHandler->expects($this->once())->method('search')
            ->with('org', 1, 10)
            ->will($this->returnValue($expectedSearchResults));

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($this->token));

        $this->token->expects($this->once())
            ->method('getOrganizationContext')
            ->will($this->returnValue($this->organization));

        $this->organization->expects($this->once())
            ->method('getIsGlobal')
            ->will($this->returnValue(false));

        $this->organization->expects($this->never())
            ->method('getId');

        $this->organizationHelper->expects($this->once())
            ->method('isGlobalOrganizationExists')
            ->willReturn(false);

        $searchResults = $this->handler->search('org', 1, 10);

        $this->assertEquals($expectedSearchResults, $searchResults);
    }

    public function testGetProperties()
    {
        $expectedProperties = ['conf' => 'value'];
        $this->baseHandler->expects($this->once())->method('getProperties')
            ->will($this->returnValue($expectedProperties));

        $properties = $this->handler->getProperties();

        $this->assertEquals($expectedProperties, $properties);
    }

    public function testGetEntityName()
    {
        $expectedEntityName = 'entityName';
        $this->baseHandler->expects($this->once())->method('getEntityName')
            ->will($this->returnValue($expectedEntityName));

        $entityName = $this->handler->getEntityName();

        $this->assertEquals($expectedEntityName, $entityName);
    }

    public function testConvertItem()
    {
        $organization   = new Organization();
        $expectedResult = ['field' => 'value'];
        $this->baseHandler->expects($this->once())->method('convertItem')
            ->with($organization)
            ->will($this->returnValue($expectedResult));

        $result = $this->handler->convertItem($organization);

        $this->assertEquals($expectedResult, $result);
    }
}
