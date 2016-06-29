<?php

namespace OroB2BPro\Bundle\WebsiteBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

use OroB2BPro\Bundle\WebsiteBundle\EventListener\FormViewListener;

class FormViewListenerTest extends \PHPUnit_Framework_TestCase
{
    const DATA_CLASS = 'data_class';
    const WEBSITE_LABEL = 'website label';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var FormViewListener
     */
    protected $formViewListener;
    /**
     * @var BeforeListRenderEvent
     */
    protected $event;
    /**
     * @var \Twig_Environment|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $environment;

    protected function setUp()
    {
        $requestStack = new RequestStack();
        $this->request = new Request();
        $requestStack->push($this->request);
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->formViewListener = new FormViewListener($requestStack, $this->registry);
        $this->formViewListener->setDataClass(self::DATA_CLASS);
        $this->formViewListener->setWebsiteLabel(self::WEBSITE_LABEL);
    }

    public function testOnEntityEdit()
    {
        $this->setEvent();
        $template = 'template';
        $this->environment
            ->expects($this->once())
            ->method('render')
            ->with('OroB2BProWebsiteBundle::website_select.html.twig', ['form' => $this->event->getFormView()])
            ->willReturn($template);
        $this->formViewListener->onEntityEdit($this->event);
        $data = $this->event->getScrollData()->getData();
        $this->assertEquals($data['dataBlocks'][0]['subblocks'][0]['data'][1], $template);
    }

    public function testOnEntityView()
    {
        $this->setEvent();
        $entityId = 42;
        $entity = new \stdClass();
        $template = 'template';
        $this->request->query->add(['id' => $entityId]);
        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $repo->expects($this->once())->method('find')->with($entityId)->willReturn($entity);
        $em->expects($this->once())->method('getRepository')->with(self::DATA_CLASS)->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::DATA_CLASS)
            ->willReturn($em);
        $this->environment
            ->expects($this->once())
            ->method('render')
            ->with(
                'OroB2BProWebsiteBundle::website_field.html.twig',
                ['label' => self::WEBSITE_LABEL, 'entity' => $entity]
            )
            ->willReturn($template);
        $this->formViewListener->onEntityView($this->event);
        $data = $this->event->getScrollData()->getData();
        $this->assertEquals($data['dataBlocks'][0]['subblocks'][0]['data'][1], $template);
    }

    protected function setEvent()
    {
        $scrollData = new ScrollData();
        $blockId = $scrollData->addBlock('some_label');
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, 'some_data');
        $this->environment = $this->getMock(\Twig_Environment::class);
        $this->event = new BeforeListRenderEvent($this->environment, $scrollData, new FormView());
    }
}
