<?php
namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken as Token;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use OroPro\Bundle\OrganizationBundle\Form\Handler\OrganizationProHandler;


class OrganizationProHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var Request */
    protected $request;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface */
    protected $form;

    /** @var BusinessUnitHandler */
    protected $handler;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager */
    protected $securityContext;

    /**
     * @var BusinessUnit
     */
    protected $entity;

    protected function setUp()
    {
        $this->manager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = new Request();
        $this->form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityContext = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContextInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity  = new Organization();
        $this->handler = new OrganizationProHandler($this->form, $this->request, $this->manager, $this->securityContext);
    }

    public function testProcessValidData()
    {
        $currentUser = new User();
        $currentUser->setId(mt_rand());

        $removedUser = new User();
        $removedUser->setId(mt_rand());

        $this->entity->addUser($removedUser);

        $token = new Token($currentUser, uniqid(), uniqid());

        $appendForm = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
        $removeForm = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();

        $this->form->expects($this->once())->method('setData')->with($this->entity);

        $this->securityContext->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->form->expects($this->at(1))
            ->method('get')
            ->with('appendUsers')
            ->willReturn($appendForm);

        $appendForm->expects($this->once())->method('setData')->with([$currentUser]);

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $appendForm->expects($this->once())
            ->method('getData')
            ->willReturn([$currentUser]);

        $this->form->expects($this->at(4))
            ->method('get')
            ->with('appendUsers')
            ->willReturn($appendForm);

        $removeForm->expects($this->once())
            ->method('getData')
            ->willReturn([$removedUser]);
        $this->form->expects($this->at(5))
            ->method('get')
            ->with('removeUsers')
            ->willReturn($removeForm);

        $this->manager->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->handler->process($this->entity));

        $users = $this->entity->getUsers()->toArray();
        $this->assertCount(1, $users);
        $this->assertEquals($currentUser, current($users));
        $this->assertFalse($this->entity->hasUser($removedUser));
    }

    public function testBadMethod()
    {
        $this->request->setMethod('GET');
        $this->assertFalse($this->handler->process($this->entity));
    }
}
