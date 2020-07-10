<?php

use CrisisTextLine\ReadReplicaRouteLimiterBundle\Annotation\ShouldNotUseReplica;
use CrisisTextLine\ReadReplicaRouteLimiterBundle\Annotation\ShouldUseReplica;
use CrisisTextLine\ReadReplicaRouteLimiterBundle\EventListener\ControllerListener;
use CrisisTextLine\ReadReplicaRouteLimiterBundle\Util\Configurations;
use Doctrine\Common\Annotations\Reader;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connections\MasterSlaveConnection;
use helpers\StdClassWithCallable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class AnnotationBehaviorTest extends TestCase
{
    /**
     * @var Reader
     */
    protected $mockReader;

    /**
     * @var Connection
     */
    protected $mockConnection;

    /**
     * @var FilterControllerEvent
     */
    protected $mockEvent;

    /**
     * @var Configurations
     */
    protected $configurations;

    /**
     * @var ControllerListener
     */
    protected $listener;

    public function setUp(): void
    {
        $this->mockReader = $this->getMockBuilder(Reader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockConnection = $this->getMockBuilder(MasterSlaveConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockEvent = $this->getMockBuilder(FilterControllerEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configurations = new Configurations($this->mockReader);
        $this->listener = new ControllerListener($this->configurations, $this->mockConnection);
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

    protected function expectsPrimary()
    {
        $this->mockConnection->expects($this->once())
            ->method('connect')
            ->with('master');
    }

    protected function expectsReplica()
    {
        $this->mockConnection->expects($this->once())
            ->method('connect')
            ->with('slave');
    }

    protected function setClassExpectation()
    {
        $classAnnotation = new ShouldUseReplica;

        $this->mockReader->expects($this->once())
            ->method('getClassAnnotation')
            ->willReturn($classAnnotation);

        return $classAnnotation;
    }

    protected function setMethodExpectation($shouldUseReplica = false, $shouldNotUseReplica = false)
    {
        $this->mockReader->expects($this->exactly(2))
            ->method('getMethodAnnotation')
            ->will($this->returnCallback(function ($function, $class) use ($shouldUseReplica, $shouldNotUseReplica) {
                switch ($class) {
                    case ShouldUseReplica::class:
                        return $shouldUseReplica ? new ShouldUseReplica : null;
                    case ShouldNotUseReplica::class:
                        return $shouldNotUseReplica ? new ShouldNotUseReplica : null;
                }
            }));
    }

    public function testItSetsPrimaryByDefault()
    {
        $this->expectsEvent();
        $this->expectsPrimary();

        $this->listener->onKernelController($this->mockEvent);
    }

    public function testItSetsPrimaryIfNotMasterSlaveSetup()
    {
        $this->doesntExpectEvent();

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $listener = new ControllerListener($this->configurations, $connection);

        $listener->onKernelController($this->mockEvent);
    }

    public function testItSetsReplicaForClass()
    {
        $this->expectsEvent();
        $this->expectsReplica();

        $this->setClassExpectation();

        $this->listener->onKernelController($this->mockEvent);
    }

    public function testItSetsReplicaForMethodOnly()
    {
        $this->expectsEvent();
        $this->expectsReplica();

        $this->setMethodExpectation(true, false);

        $this->listener->onKernelController($this->mockEvent);
    }

    public function testItSetsReplicaForClassAndMethod()
    {
        $this->expectsEvent();
        $this->expectsReplica();

        $this->setClassExpectation();
        $this->setMethodExpectation(true, false);

        $this->listener->onKernelController($this->mockEvent);
    }

    public function testItSetsPrimaryIfOverridden()
    {
        $this->expectsEvent();
        $this->expectsPrimary();

        $this->setClassExpectation();
        $this->setMethodExpectation(false, true);

        $this->listener->onKernelController($this->mockEvent);
    }

    public function testItSetsPrimaryIfNoParentButOverriddenAnyway()
    {
        $this->expectsEvent();
        $this->expectsPrimary();

        $this->setMethodExpectation(false, true);

        $this->listener->onKernelController($this->mockEvent);
    }
}
