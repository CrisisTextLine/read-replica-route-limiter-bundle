<?php

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use helpers\StdClassWithCallable;
use Doctrine\DBAL\Connections\MasterSlaveConnection;
use CJCodes\SlaveRouteLimiterBundle\Util\Configurations;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use CJCodes\SlaveRouteLimiterBundle\EventListener\ControllerListener;

class ControllerListenerTest extends TestCase
{
    /**
     * @var ControllerListener
     */
    protected $SUT;

    /**
     * @var MasterSlaveConnection
     */
    protected $mockConnection;

    public function setUp()
    {
        $this->mockConfigurations = $this->getMockBuilder(Configurations::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockConnection = $this->getMockBuilder(MasterSlaveConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockEvent = $this->getMockBuilder(FilterControllerEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->SUT = new ControllerListener($this->mockConfigurations, $this->mockConnection);
    }

    protected function expectsEvent()
    {
        $callable = new StdClassWithCallable;
        $this->mockEvent->expects($this->once())
            ->method('getController')
            ->willReturn([$callable, 'tester']);
    }

    protected function doesntExpectEvent()
    {
        $this->mockEvent->expects($this->never())
            ->method('getController');
    }

    public function testItSetsMasterByDefault()
    {
        $this->expectsEvent();

        $this->mockConnection->expects($this->once())
            ->method('connect')
            ->with('master');

        $this->SUT->onKernelController($this->mockEvent);
    }

    public function testItBypassesIfNotMasterSlaveConnection()
    {
        $this->doesntExpectEvent();

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tempSUT = new ControllerListener($this->mockConfigurations, $connection);

        $tempSUT->onKernelController($this->mockEvent);
    }

    public function testItSetsSlave()
    {
        $this->expectsEvent();

        $this->mockConfigurations->expects($this->once())
            ->method('shouldUseSlave')
            ->willReturn(true);

        $this->mockConnection->expects($this->once())
            ->method('connect')
            ->with('slave');

        $this->SUT->onKernelController($this->mockEvent);
    }

    public function testItSetsMaster()
    {
        $this->expectsEvent();

        $this->mockConfigurations->expects($this->once())
            ->method('shouldUseSlave')
            ->willReturn(false);

        $this->mockConnection->expects($this->once())
            ->method('connect')
            ->with('master');

        $this->SUT->onKernelController($this->mockEvent);
    }
}
