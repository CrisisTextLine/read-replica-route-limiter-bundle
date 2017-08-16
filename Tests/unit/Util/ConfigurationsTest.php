<?php

use PHPUnit\Framework\TestCase;
use helpers\StdClassWithCallable;
use Doctrine\Common\Annotations\Reader;
use CJCodes\SlaveRouteLimiterBundle\Util\Configurations;
use CJCodes\SlaveRouteLimiterBundle\Annotation\ShouldUseSlave;
use CJCodes\SlaveRouteLimiterBundle\Annotation\ShouldNotUseSlave;

class ConfigurationsTest extends TestCase
{
    /**
     * @var Configurations
     */
    protected $SUT;

    public function setUp()
    {
        $mockReader = $this->getMockBuilder(Reader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockReader = $mockReader;

        $this->mockCallable = [
            new StdClassWithCallable,
            'tester'
        ];

        $this->SUT = new Configurations($mockReader);
    }

    protected function setClassExpectation()
    {
        $classAnnotation = new ShouldUseSlave;

        $this->mockReader->expects($this->once())
            ->method('getClassAnnotation')
            ->willReturn($classAnnotation);

        return $classAnnotation;
    }

    protected function setMethodExpectation($shouldUseSlave = null, $shouldNotUseSlave = null)
    {
        $this->mockReader->expects($this->exactly(2))
            ->method('getMethodAnnotation')
            ->will($this->returnCallback(function ($function, $class) use ($shouldUseSlave, $shouldNotUseSlave) {
                switch ($class) {
                    case ShouldUseSlave::class:
                        return $shouldUseSlave;
                    case ShouldNotUseSlave::class:
                        return $shouldNotUseSlave;
                }
            }));
    }

    public function testItSetsPublicVariables()
    {
        $classAnnotation = $this->setClassExpectation();

        $methodAnnotation = null;
        $negationAnnotation = new ShouldNotUseSlave;

        $this->setMethodExpectation($methodAnnotation, $negationAnnotation);

        $this->SUT->parse($this->mockCallable);

        $this->assertEquals($classAnnotation, $this->SUT->class);
        $this->assertEquals($methodAnnotation, $this->SUT->method);
        $this->assertEquals($negationAnnotation, $this->SUT->negation);
    }

    public function testItShouldUseSlaveForClasses()
    {
        $this->setClassExpectation();

        $this->SUT->parse($this->mockCallable);

        $this->assertTrue($this->SUT->shouldUseSlave());
    }

    public function testItShouldNotUseSlaveForNullValues()
    {
        $this->SUT->parse($this->mockCallable);

        $this->assertFalse($this->SUT->shouldUseSlave());
    }

    public function testItShouldNotUseSlaveForMethodThatHasOverride()
    {
        $this->setClassExpectation();

        $this->setMethodExpectation(null, new ShouldNotUseSlave);

        $this->SUT->parse($this->mockCallable);

        $this->assertFalse($this->SUT->shouldUseSlave());
    }
}
