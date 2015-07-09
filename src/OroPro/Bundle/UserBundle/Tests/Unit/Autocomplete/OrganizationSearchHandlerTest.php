<?php

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Autocomplete\OrganizationSearchHandler as BaseOrganizationSearchHandler;
use Oro\Bundle\UserBundle\Entity\User;

use OroPro\Bundle\UserBundle\Autocomplete\OrganizationSearchHandler;

class OrganizationSearchHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrganizationSearchHandler
     */
    protected $handler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|BaseOrganizationSearchHandler
     */
    protected $baseHandler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ServiceLink
     */
    protected $serviceLink;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|User
     */
    protected $user;

    protected function setUp()
    {
        $this->baseHandler = $this
            ->getMockBuilder('Oro\Bundle\OrganizationBundle\Autocomplete\OrganizationSearchHandler')
            ->disableOriginalConstructor()
            ->getMock();
        $this->serviceLink = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new OrganizationSearchHandler($this->baseHandler, $this->serviceLink);
    }

    protected function setUpServiceLinkMock()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityContextInterface $service */
        $service = $this->getMock('Symfony\Component\Security\Core\SecurityContextInterface');
        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $token */
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        /** @var \PHPUnit_Framework_MockObject_MockObject|User $user */
        $this->serviceLink->expects($this->once())->method('getService')->will($this->returnValue($service));
        $service->expects($this->once())->method('getToken')->will($this->returnValue($token));

        $this->user = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $token->expects($this->once())->method('getUser')->will($this->returnValue($this->user));
    }

    public function testSearchIfUserAssignedToGlobalOrganization()
    {
        $this->setUpServiceLinkMock();

        $expectedSearchResults = ['results' => [['id' => 1], ['id' => 2]]];
        $this->baseHandler->expects($this->once())->method('search')
            ->with('org', 1, 10)
            ->will($this->returnValue($expectedSearchResults));

        $globalOrganization = $this
            ->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()
            ->setMethods(['getIsGlobal'])
            ->getMock();
        $regularOrganization = $this
            ->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()
            ->setMethods(['getIsGlobal'])
            ->getMock();
        $globalOrganization->expects($this->once())->method('getIsGlobal')->will($this->returnValue(true));
        $regularOrganization->expects($this->never())->method('getIsGlobal')->will($this->returnValue(false));

        $userOrganizations = new ArrayCollection(
            [
                $globalOrganization,
                $regularOrganization
            ]
        );

        $this->user->expects($this->once())->method('getOrganizations')
            ->will($this->returnValue($userOrganizations));

        $searchResults = $this->handler->search('org', 1, 10);

        $this->assertEquals($expectedSearchResults, $searchResults);
    }

    public function testSearchIfUserAssignedToNonGlobalOrganizations()
    {
        $this->setUpServiceLinkMock();

        $expectedSearchResults = ['results' => [['id' => 1], ['id' => 2]]];

        $this->baseHandler->expects($this->once())->method('search')
            ->with('org', 1, 10)
            ->will($this->returnValue(['results' => [['id' => 1], ['id' => 2], ['id' => 3]]]));

        $firstRegularOrganization = $this
            ->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getIsGlobal'])
            ->getMock();
        $secondRegularOrganization = $this
            ->getMockBuilder('Oro\Bundle\OrganizationBundle\Entity\Organization')
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getIsGlobal'])
            ->getMock();
        $firstRegularOrganization->expects($this->once())->method('getIsGlobal')->will($this->returnValue(false));
        $firstRegularOrganization->expects($this->once())->method('getId')->will($this->returnValue(1));
        $secondRegularOrganization->expects($this->once())->method('getIsGlobal')->will($this->returnValue(false));
        $secondRegularOrganization->expects($this->once())->method('getId')->will($this->returnValue(2));

        $userOrganizations = new ArrayCollection(
            [
                $firstRegularOrganization,
                $secondRegularOrganization
            ]
        );

        $this->user->expects($this->once())->method('getOrganizations')
            ->will($this->returnValue($userOrganizations));

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
        $organization = new Organization();
        $expectedResult = ['field' => 'value'];
        $this->baseHandler->expects($this->once())->method('convertItem')
            ->with($organization)
            ->will($this->returnValue($expectedResult));

        $result = $this->handler->convertItem($organization);

        $this->assertEquals($expectedResult, $result);
    }
}
