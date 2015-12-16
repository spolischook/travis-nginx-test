<?php

namespace OroPro\Bundle\EwsBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use OroPro\Bundle\EwsBundle\Provider\EwsServiceConfigurator;

class EwsServiceConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var EwsServiceConfigurator */
    protected $configurator;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $cmMock;

    /** @var Mcrypt */
    protected $encryptor;

    protected function setUp()
    {
        $this->cmMock  = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->encryptor = new Mcrypt();

        $this->configurator = new EwsServiceConfigurator($this->cmMock, $this->encryptor, 'http://localhost/?wsdl');
    }

    public function testGetters()
    {
        $this->cmMock->expects($this->at(0))
            ->method('get')
            ->with('oro_pro_ews.server')
            ->will($this->returnValue('localhost'));

        $this->cmMock->expects($this->at(1))
            ->method('get')
            ->with('oro_pro_ews.login')
            ->will($this->returnValue('test'));

        $this->cmMock->expects($this->at(2))
            ->method('get')
            ->with('oro_pro_ews.password')
            ->will($this->returnValue($this->encryptor->encryptData('test')));

        $this->cmMock->expects($this->at(3))
            ->method('get')
            ->with('oro_pro_ews.version')
            ->will($this->returnValue('online'));

        $this->cmMock->expects($this->at(4))
            ->method('get')
            ->with('oro_pro_ews.domain_list')
            ->will($this->returnValue(['test.com']));

        $this->assertEquals('localhost', $this->configurator->getServer());
        $this->assertEquals('http://localhost/?wsdl', $this->configurator->getEndpoint());
        $this->assertEquals('test', $this->configurator->getLogin());
        $this->assertEquals('test', $this->configurator->getPassword());

        $this->assertEquals('online', $this->configurator->getVersion());
        $this->assertEquals(['test.com'], $this->configurator->getDomains());

        $this->assertFalse($this->configurator->isIgnoreFailedResponseMessages());
    }
}
