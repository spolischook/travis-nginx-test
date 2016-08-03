<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\EventListener\Datagrid;

use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use OroCRM\Bundle\ChannelBundle\Entity\Channel;
use OroCRM\Bundle\MagentoBundle\Entity\MagentoSoapTransport;


use OroCRM\Bundle\MagentoBundle\EventListener\UpdateIntegrationConnectorsListener;
use OroCRM\Bundle\MagentoBundle\Provider\ChannelType;
use OroCRM\Bundle\MagentoBundle\Provider\Transport\SoapTransport;


use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use OroCRM\Bundle\MagentoBundle\EventListener\CustomerDataGridListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use OroCRM\Bundle\MagentoBundle\EventListener\StoreGridListener;

class StoreGridListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var StoreGridListener */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var string */
    protected $dataChannelClass;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $datagrid;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $qb;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $datasource;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->dataChannelClass = 'Oro\Bundle\IntegrationBundle\Entity\Channel';

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();

        $this->listener = new StoreGridListener(
            $this->securityFacade,
            $this->dataChannelClass,
            $this->entityManager
        );

        $this->datagrid = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface')
            ->getMock();

        $this->qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testWebsiteConditionForStores()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_integration_assign')
            ->will($this->returnValue(true));

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($this->datasource));

        $this->datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($this->qb));

        $this->qb->expects($this->once())
            ->method('andWhere')
            ->with('w.originId = :id')
            ->will($this->returnSelf());

        $this->datagrid->expects($this->any())
            ->method('getParameters')
            ->will($this->returnValue($this->createParameterBag(['channelIds' => 100 ])));

        $transport = new MagentoSoapTransport();
        $integration = new Integration();
        $transport->setWebsiteId(1);
        $integration->setTransport($transport);
        $cnannel = new Channel();
        $cnannel->setDataSource($integration);

        $this->entityManager->expects($this->any())
            ->method('find')
            ->will($this->returnValue($cnannel));

        $event = new BuildAfter($this->datagrid);
        $this->listener->onBuildAfter($event);
        $dataGrid2 = $event->getDatagrid();

    }

    public function testOnBuildAfterAclIntegrationAssignGranted()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_integration_assign')
            ->will($this->returnValue(true));

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($this->datasource));

        $this->datagrid->expects($this->any())
            ->method('getParameters')
            ->will($this->returnValue($this->createParameterBag(['channelIds' => 100 ])));

        $this->qb->expects($this->never())
            ->method('andWhere')
            ->with('1 = 0')
            ->will($this->returnSelf());

        $event = new BuildAfter($this->datagrid);
        $this->listener->onBuildAfter($event);
    }

    public function testOnBuildAfterAclIntegrationAssignNotGranted()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_integration_assign')
            ->will($this->returnValue(false));

        $this->datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($this->qb));

        $this->datagrid->expects($this->once())
            ->method('getDatasource')
            ->will($this->returnValue($this->datasource));

        $this->qb->expects($this->at(0))
            ->method('andWhere')
            ->with('1 = 0')
            ->will($this->returnSelf());

        $event = new BuildAfter($this->datagrid);
        $this->listener->onBuildAfter($event);
    }

    /**
     * @param array $data
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createParameterBag(array $data)
    {
        $parameters = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\ParameterBag')->getMock();

        $parameters->expects($this->any())
            ->method('get')
            ->will(
                $this->returnCallback(
                    function ($key) use ($data) {
                        return $data[$key];
                    }
                )
            );

        return $parameters;
    }
}
