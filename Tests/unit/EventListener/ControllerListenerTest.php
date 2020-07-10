<?php

use CrisisTextLine\ReadReplicaRouteLimiterBundle\EventListener\ControllerListener;
use CrisisTextLine\ReadReplicaRouteLimiterBundle\Util\Configurations;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connections\MasterSlaveConnection;
use helpers\StdClassWithCallable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

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

    public function setUp(): void
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

    public function testItSetsPrimaryByDefault()
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

    public function testItSetsReplica()
    {
        $this->expectsEvent();

        $this->mockConfigurations->expects($this->once())
            ->method('shouldUseReplica')
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
            ->method('shouldUseReplica')
            ->willReturn(false);

        $this->mockConnection->expects($this->once())
            ->method('connect')
            ->with('master');

        $this->SUT->onKernelController($this->mockEvent);
    }
}
