<?php

namespace OroB2BPro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Testing\Unit\FormViewListenerTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

use OroB2BPro\Bundle\PricingBundle\EventListener\AccountGroupFormViewListener;

class AccountGroupFormViewListenerTest extends FormViewListenerTestCase
{
    use EntityTrait;

    /**
     * @var WebsiteProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->websiteProvider = $this->getMock('OroB2B\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface');
        parent::setUp();
    }

    protected function tearDown()
    {
        unset($this->doctrineHelper, $this->translator, $this->websiteProvider);
    }

    public function testOnViewNoRequest()
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        $listener = $this->getListener($requestStack);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->getMock('\Twig_Environment');
        $event = $this->createEvent($env);
        $listener->onAccountGroupView($event);
    }

    public function testOnAccountGroupView()
    {
        $accountGroupId = 1;
        $accountGroup = new AccountGroup();
        $websiteId1 = 12;
        $websiteId2 = 13;
        $websiteId3 = 14;

        /** @var Website $website1 */
        $website1 = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', ['id' => $websiteId1]);
        /** @var Website $website2 */
        $website2 = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', ['id' => $websiteId2]);
        /** @var Website $website3 */
        $website3 = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', ['id' => $websiteId3]);

        $websites = [$website1, $website2, $website3];

        $priceListToAccountGroup1 = new PriceListToAccountGroup();
        $priceListToAccountGroup1->setAccountGroup($accountGroup);
        $priceListToAccountGroup1->setWebsite($website1);
        $priceListToAccountGroup1->setPriority(3);
        $priceListToAccountGroup2 = clone $priceListToAccountGroup1;
        $priceListToAccountGroup2->setWebsite($website2);
        $priceListsToAccountGroup = [$priceListToAccountGroup1, $priceListToAccountGroup2];

        $templateHtml = 'template_html';

        $fallbackEntity = new PriceListAccountGroupFallback();
        $fallbackEntity->setAccountGroup($accountGroup);
        $fallbackEntity->setWebsite($website3);
        $fallbackEntity->setFallback(PriceListAccountFallback::CURRENT_ACCOUNT_ONLY);

        $request = new Request(['id' => $accountGroupId]);
        $requestStack = $this->getRequestStack($request);

        /** @var AccountGroupFormViewListener $listener */
        $listener = $this->getListener($requestStack);

        $this->setRepositoryExpectationsForAccountGroup(
            $websites,
            $accountGroup,
            $priceListsToAccountGroup,
            $fallbackEntity
        );

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with(
                'OroB2BProPricingBundle:Account:price_list_view.html.twig',
                [
                    'priceListsByWebsites' => [
                        $websiteId1 => [$priceListToAccountGroup1],
                        $websiteId2 => [$priceListToAccountGroup2],
                    ],
                    'fallbackByWebsites' => [
                        $websiteId3 => PriceListAccountGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY,
                    ],
                    'websites' => [$website1, $website2, $website3],
                    'choices' => [
                        'orob2b.pricing.fallback.website.label',
                        'orob2b.pricing.fallback.current_account_group_only.label',
                    ],
                ]
            )
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment);
        $listener->onAccountGroupView($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertEquals(
            [$templateHtml],
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    public function testOnEntityEdit()
    {
        $formView = new FormView();
        $templateHtml = 'template_html';

        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

        /** @var AccountGroupFormViewListener $listener */
        $listener = $this->getListener($requestStack);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->getMock('\Twig_Environment');

        $environment->expects($this->once())
            ->method('render')
            ->with('OroB2BProPricingBundle:Account:price_list_update.html.twig', ['form' => $formView])
            ->willReturn($templateHtml);

        $event = $this->createEvent($environment, $formView);
        $listener->onEntityEdit($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertEquals(
            [$templateHtml],
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }


    /**
     * @param Website[] $websites
     * @param AccountGroup $accountGroup
     * @param PriceListToAccountGroup[] $priceListsToAccountGroup
     * @param PriceListAccountGroupFallback $fallbackEntity
     */
    protected function setRepositoryExpectationsForAccountGroup(
        $websites,
        AccountGroup $accountGroup,
        $priceListsToAccountGroup,
        PriceListAccountGroupFallback $fallbackEntity
    ) {
        $this->websiteProvider->expects($this->once())
            ->method('getWebsites')
            ->willReturn($websites);
        
        $priceToAccountGroupRepository = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository')
            ->disableOriginalConstructor()
            ->getMock();
        
        $priceToAccountGroupRepository->expects($this->once())
            ->method('findBy')
            ->with(['accountGroup' => $accountGroup])
            ->willReturn($priceListsToAccountGroup);
        
        $fallbackRepository = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository')
            ->disableOriginalConstructor()
            ->getMock();
        
        $fallbackRepository->expects($this->once())
            ->method('findBy')
            ->with(['accountGroup' => $accountGroup])
            ->willReturn([$fallbackEntity]);
        
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($accountGroup);
        
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['OroB2BPricingBundle:PriceListToAccountGroup', $priceToAccountGroupRepository],
                        ['OroB2BPricingBundle:PriceListAccountGroupFallback', $fallbackRepository]
                    ]
                )
            );
    }


    /**
     * @param array $scrollData
     * @param string $html
     */
    protected function assertScrollDataPriceBlock(array $scrollData, $html)
    {
        $this->assertEquals(
            'orob2b.pricing.productprice.entity_plural_label.trans',
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::TITLE]
        );
        $this->assertEquals(
            [$html],
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    /**
     * @param \Twig_Environment $environment
     * @param FormView $formView
     * @return BeforeListRenderEvent
     */
    protected function createEvent(\Twig_Environment $environment, FormView $formView = null)
    {
        $defaultData = [
            ScrollData::DATA_BLOCKS => [
                [
                    ScrollData::SUB_BLOCKS => [
                        [
                            ScrollData::DATA => [],
                        ]
                    ]
                ]
            ]
        ];

        return new BeforeListRenderEvent($environment, new ScrollData($defaultData), $formView);
    }

    /**
     * @param RequestStack $requestStack
     * @return AccountGroupFormViewListener
     */
    protected function getListener(RequestStack $requestStack)
    {
        return new AccountGroupFormViewListener(
            $requestStack,
            $this->translator,
            $this->doctrineHelper,
            $this->websiteProvider
        );
    }

    /**
     * @param $request
     * @return \PHPUnit_Framework_MockObject_MockObject|RequestStack
     */
    protected function getRequestStack($request)
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);

        return $requestStack;
    }
}
