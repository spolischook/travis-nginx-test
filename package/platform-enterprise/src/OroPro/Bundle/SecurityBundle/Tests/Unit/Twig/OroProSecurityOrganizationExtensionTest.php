<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Twig;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\UserBundle\Entity\User;
use OroPro\Bundle\SecurityBundle\Tests\Unit\Fixture\GlobalOrganization;
use OroPro\Bundle\SecurityBundle\Twig\OroProSecurityOrganizationExtension;

class OroProSecurityOrganizationExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityContext;

    /** @var OroProSecurityOrganizationExtension */
    protected $extension;

    public function setUp()
    {
        $this->securityContext = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension       = new OroProSecurityOrganizationExtension($this->securityContext);
    }

    /**
     * @dataProvider organizationTestProvider
     * @param ArrayCollection $organizations
     * @param array           $expectResult
     */
    public function testGetOrganizations(ArrayCollection $organizations, $expectResult)
    {
        $user = new User();
        $user->setOrganizations($organizations);
        $token = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityContext->expects($this->once())->method('getToken')->willReturn($token);
        $token->expects($this->once())->method('getUser')->willReturn($user);

        $this->assertEquals($expectResult, $this->extension->getOrganizations());
    }

    public function organizationTestProvider()
    {
        $globalOrg = new GlobalOrganization();
        $globalOrg->setName('global');
        $globalOrg->setId(123);
        $globalOrg->setIsGlobal(true);
        $globalOrg->setEnabled(true);

        $firstOrg = new GlobalOrganization();
        $firstOrg->setName('first');
        $firstOrg->setId(1);
        $firstOrg->setIsGlobal(false);
        $firstOrg->setEnabled(true);

        $secondOrg = new GlobalOrganization();
        $secondOrg->setName('second');
        $secondOrg->setId(450);
        $secondOrg->setIsGlobal(false);
        $secondOrg->setEnabled(true);

        $disabledOrg = new GlobalOrganization();
        $disabledOrg->setName('disabled');
        $disabledOrg->setId(45);
        $disabledOrg->setIsGlobal(false);
        $disabledOrg->setEnabled(false);

        return [
            [
                new ArrayCollection(),
                []
            ],
            [
                new ArrayCollection([$disabledOrg]),
                []
            ],
            [
                new ArrayCollection([$firstOrg, $globalOrg, $secondOrg, $disabledOrg]),
                [
                    ['id' => 123, 'name' => 'global'],
                    ['id' => 1, 'name' => '&nbsp;&nbsp;&nbsp;first'],
                    ['id' => 450, 'name' => '&nbsp;&nbsp;&nbsp;second'],
                ]
            ],
            [
                new ArrayCollection([$firstOrg, $secondOrg, $disabledOrg]),
                [
                    ['id' => 1, 'name' => 'first'],
                    ['id' => 450, 'name' => 'second'],
                ]
            ],
        ];
    }
}
