<?php

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use helpers\StdClassWithCallable;
use Doctrine\Common\Annotations\Reader;
use Doctrine\DBAL\Connections\MasterSlaveConnection;
use CJCodes\SlaveRouteLimiterBundle\Util\Configurations;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use CJCodes\SlaveRouteLimiterBundle\Annotation\ShouldUseSlave;
use CJCodes\SlaveRouteLimiterBundle\Annotation\ShouldNotUseSlave;
use CJCodes\SlaveRouteLimiterBundle\EventListener\ControllerListener;

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

    public function setUp()
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

    protected function expectsMaster()
    {
        $this->mockConnection->expects($this->once())
            ->method('connect')
            ->with('master');
    }

    protected function expectsSlave()
    {
        $this->mockConnection->expects($this->once())
            ->method('connect')
            ->with('slave');
    }

    protected function setClassExpectation()
    {
        $classAnnotation = new ShouldUseSlave;

        $this->mockReader->expects($this->once())
            ->method('getClassAnnotation')
            ->willReturn($classAnnotation);

        return $classAnnotation;
    }

    protected function setMethodExpectation($shouldUseSlave = false, $shouldNotUseSlave = false)
    {
        $this->mockReader->expects($this->exactly(2))
            ->method('getMethodAnnotation')
            ->will($this->returnCallback(function ($function, $class) use ($shouldUseSlave, $shouldNotUseSlave) {
                switch ($class) {
                    case ShouldUseSlave::class:
                        return $shouldUseSlave ? new ShouldUseSlave : null;
                    case ShouldNotUseSlave::class:
                        return $shouldNotUseSlave ? new ShouldNotUseSlave : null;
                }
            }));
    }

    public function testItSetsMasterByDefault()
    {
        $this->expectsEvent();
        $this->expectsMaster();

        $this->listener->onKernelController($this->mockEvent);
    }

    public function testItSetsMasterIfNotMasterSlaveSetup()
    {
        $this->doesntExpectEvent();

        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $listener = new ControllerListener($this->configurations, $connection);

        $listener->onKernelController($this->mockEvent);
    }

    public function testItSetsSlaveForClass()
    {
        $this->expectsEvent();
        $this->expectsSlave();

        $this->setClassExpectation();

        $this->listener->onKernelController($this->mockEvent);
    }

    public function testItSetsSlaveForMethodOnly()
    {
        $this->expectsEvent();
        $this->expectsSlave();

        $this->setMethodExpectation(true, false);

        $this->listener->onKernelController($this->mockEvent);
    }

    public function testItSetsSlaveForClassAndMethod()
    {
        $this->expectsEvent();
        $this->expectsSlave();

        $this->setClassExpectation();
        $this->setMethodExpectation(true, false);

        $this->listener->onKernelController($this->mockEvent);
    }

    public function testItSetsMasterIfOverridden()
    {
        $this->expectsEvent();
        $this->expectsMaster();

        $this->setClassExpectation();
        $this->setMethodExpectation(false, true);

        $this->listener->onKernelController($this->mockEvent);
    }

    public function testItSetsMasterIfNoParentButOverriddenAnyway()
    {
        $this->expectsEvent();
        $this->expectsMaster();

        $this->setMethodExpectation(false, true);

        $this->listener->onKernelController($this->mockEvent);
    }
}
