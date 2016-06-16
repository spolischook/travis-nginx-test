<?php

namespace OroPro\Bundle\SecurityBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;

use OroPro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;
use OroPro\Bundle\SecurityBundle\Acl\Domain\OrganizationSecurityIdentity;
use OroPro\Bundle\SecurityBundle\Form\Handler\ShareHandler;
use OroPro\Bundle\SecurityBundle\Form\Model\Share;

class ShareHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var \PHPUnit_Framework_MockObject_MockObject|MutableAclProvider */
    protected $aclProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigProvider */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AclExtensionSelector */
    protected $aclExtensionSelector;

    /** @var ShareHandler */
    protected $handler;

    /** @var TestEmployee */
    protected $entity;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigInterface */
    protected $config;

    protected function setUp()
    {
        $this->form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->request = new Request();

        $this->aclProvider = $this->getMockBuilder('Symfony\Component\Security\Acl\Dbal\MutableAclProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');

        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->aclExtensionSelector = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new ShareHandler(
            $this->form,
            $this->request,
            $this->aclProvider,
            $this->manager,
            $this->configProvider,
            $this->aclExtensionSelector
        );

        $this->entity = new TestEmployee();
        $this->entity->setId(42);
    }

    /**
     * @dataProvider processExceptionDataProvider
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage Sharing scopes are disabled
     *
     * @param bool $hasConfig
     */
    public function testProcessException($hasConfig)
    {
        $this->config->expects($hasConfig ? $this->once() : $this->never())
            ->method('get')
            ->with('share_scopes')
            ->willReturn([]);

        $this->configProvider->expects($this->once())->method('hasConfig')->willReturn($hasConfig);
        $this->configProvider->expects($hasConfig ? $this->once() : $this->never())
            ->method('getConfig')
            ->willReturn($this->config);

        $this->assertFalse($this->handler->process(new Share(), $this->entity));
    }

    /**
     * @return array
     */
    public function processExceptionDataProvider()
    {
        return [
            [
                'hasConfig' => false
            ],
            [
                'hasConfig' => true
            ]
        ];
    }

    public function testProcessUnsupportedRequest()
    {
        $this->assertConfigProviderCalled();

        $this->request->setMethod('GET');

        $entityClassForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $entityClassForm->expects($this->once())->method('setData')->with(get_class($this->entity));

        $entityIdForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $entityIdForm->expects($this->once())->method('setData')->with($this->entity->getId());

        $this->form->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['entityClass', $entityClassForm],
                    ['entityId', $entityIdForm]
                ]
            );
        $this->form->expects($this->never())->method('submit');
        $this->form->expects($this->never())->method('setData');

        $this->assertFalse($this->handler->process(new Share(), $this->entity));
    }

    /**
     * @dataProvider supportedMethods
     *
     * @param string $method
     * @param boolean $isValid
     * @param boolean $isProcessed
     * @param object $entity
     * @param SecurityIdentityInterface $sid
     * @param int $requiredMask
     */
    public function testProcessSupportedRequest(
        $method,
        $isValid,
        $isProcessed,
        $entity = null,
        SecurityIdentityInterface $sid = null,
        $requiredMask = 0
    ) {
        $model = new Share();
        $model->setEntities([$entity]);

        $this->request->setMethod($method);

        $this->form->expects($this->once())->method('setData')->with($model);
        $this->form->expects($this->once())->method('isValid')->willReturn($isValid);
        $this->form->expects($this->once())->method('submit')->with($this->request);

        $this->assertConfigProviderCalled();

        if ($isProcessed) {
            $this->assertAclProviderCalled($sid, $requiredMask);
            $this->assertAclExtensionSelectorCalled();
        }

        $this->assertEquals($isProcessed, $this->handler->process($model, $this->entity));
    }

    /**
     * @return array
     */
    public function supportedMethods()
    {
        $user = new User();
        $user->setUsername('test_username');

        /** @var BusinessUnit $businessUnit */
        $businessUnit = $this->getEntity('Oro\Bundle\OrganizationBundle\Entity\BusinessUnit', ['id' => 35]);

        /** @var Organization $organization */
        $organization = $this->getEntity('Oro\Bundle\OrganizationBundle\Entity\Organization', ['id' => 100]);

        return [
            'post User entity' => [
                'method' => 'POST',
                'isValid' => true,
                'isProcessed' => true,
                'enitity' => $user,
                'sid' => new UserSecurityIdentity('test_username', 'Oro\Bundle\UserBundle\Entity\User'),
                'requiredMask' => 43 // identity + 1 << 0
            ],
            'put BusinessUnit entity' => [
                'method' => 'PUT',
                'isValid' => true,
                'isProcessed' => true,
                'enitity' => $businessUnit,
                'sid' => new BusinessUnitSecurityIdentity(35, 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit'),
                'requiredMask' => 44 // identity + 1 << 1
            ],
            'post Organization entity' => [
                'method' => 'POST',
                'isValid' => true,
                'isProcessed' => true,
                'enitity' => $organization,
                'sid' => new OrganizationSecurityIdentity(100, 'Oro\Bundle\OrganizationBundle\Entity\Organization'),
                'requiredMask' => 50 // identity + 1 << 3
            ],
            'invalid' => [
                'method' => 'POST',
                'isValid' => false,
                'isProcessed' => false
            ],
        ];
    }

    protected function assertConfigProviderCalled()
    {
        $this->config->expects($this->once())
            ->method('get')
            ->with('share_scopes')
            ->willReturn([Share::SHARE_SCOPE_USER, Share::SHARE_SCOPE_BUSINESS_UNIT, Share::SHARE_SCOPE_ORGANIZATION]);

        $this->configProvider->expects($this->once())->method('hasConfig')->willReturn(true);
        $this->configProvider->expects($this->once())->method('getConfig')->willReturn($this->config);
    }

    /**
     * @param SecurityIdentityInterface $sid
     * @param int $requiredMask
     * @return \PHPUnit_Framework_MockObject_MockObject|MutableAclInterface
     */
    protected function assertAclProviderCalled(SecurityIdentityInterface $sid, $requiredMask)
    {
        $sidOld = new UserSecurityIdentity('test', 'stdClass');

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntryInterface $aceOld */
        $aceOld = $this->getMock('Symfony\Component\Security\Acl\Model\EntryInterface');
        $aceOld->expects($this->atLeastOnce())->method('getSecurityIdentity')->willReturn($sidOld);

        /** @var \PHPUnit_Framework_MockObject_MockObject|MutableAclInterface $acl */
        $acl = $this->getMock('Symfony\Component\Security\Acl\Model\MutableAclInterface');
        $acl->expects($this->atLeastOnce())->method('getObjectAces')->willReturn([$aceOld]);
        $acl->expects($this->once())->method('deleteObjectAce')->willReturn(0);
        $acl->expects($this->once())->method('insertObjectAce')->with($sid, $requiredMask);

        $this->aclProvider->expects($this->once())
            ->method('findAcl')
            ->with(new ObjectIdentity($this->entity->getId(), get_class($this->entity)))
            ->willReturn($acl);
        $this->aclProvider->expects($this->once())->method('updateAcl')->with($acl);

        return $acl;
    }

    protected function assertAclExtensionSelectorCalled()
    {
        $maskBuilder = new EntityMaskBuilder(42, ['VIEW']);

        /** @var \PHPUnit_Framework_MockObject_MockObject|AclExtensionInterface $extension */
        $extension = $this->getMock('Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface');
        $extension->expects($this->once())->method('getMaskBuilder')->with('VIEW')->willReturn($maskBuilder);

        $this->aclExtensionSelector->expects($this->once())
            ->method('select')
            ->with('entity:(root)')
            ->willReturn($extension);
    }
}
