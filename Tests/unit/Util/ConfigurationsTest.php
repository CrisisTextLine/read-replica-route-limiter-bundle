<?php

use CrisisTextLine\ReadReplicaRouteLimiterBundle\Annotation\ShouldNotUseReplica;
use CrisisTextLine\ReadReplicaRouteLimiterBundle\Annotation\ShouldUseReplica;
use CrisisTextLine\ReadReplicaRouteLimiterBundle\Util\Configurations;
use Doctrine\Common\Annotations\Reader;
use helpers\StdClassWithCallable;
use PHPUnit\Framework\TestCase;

class ConfigurationsTest extends TestCase
{
    /**
     * @var Configurations
     */
    protected $SUT;

    public function setUp(): void
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
        $classAnnotation = new ShouldUseReplica;

        $this->mockReader->expects($this->once())
            ->method('getClassAnnotation')
            ->willReturn($classAnnotation);

        return $classAnnotation;
    }

    protected function setMethodExpectation($shouldUseReplica = null, $shouldNotUseReplica = null)
    {
        $this->mockReader->expects($this->exactly(2))
            ->method('getMethodAnnotation')
            ->will($this->returnCallback(function ($function, $class) use ($shouldUseReplica, $shouldNotUseReplica) {
                switch ($class) {
                    case ShouldUseReplica::class:
                        return $shouldUseReplica;
                    case ShouldNotUseReplica::class:
                        return $shouldNotUseReplica;
                }
            }));
    }

    public function testItSetsPublicVariables()
    {
        $classAnnotation = $this->setClassExpectation();

        $methodAnnotation = null;
        $negationAnnotation = new ShouldNotUseReplica;

        $this->setMethodExpectation($methodAnnotation, $negationAnnotation);

        $this->SUT->parse($this->mockCallable);

        $this->assertEquals($classAnnotation, $this->SUT->class);
        $this->assertEquals($methodAnnotation, $this->SUT->method);
        $this->assertEquals($negationAnnotation, $this->SUT->negation);
    }

    public function testItShouldUseReplicaForClasses()
    {
        $this->setClassExpectation();

        $this->SUT->parse($this->mockCallable);

        $this->assertTrue($this->SUT->shouldUseReplica());
    }

    public function testItShouldNotUseReplicaForNullValues()
    {
        $this->SUT->parse($this->mockCallable);

        $this->assertFalse($this->SUT->shouldUseReplica());
    }

    public function testItShouldNotUseReplicaForMethodThatHasOverride()
    {
        $this->setClassExpectation();

        $this->setMethodExpectation(null, new ShouldNotUseReplica);

        $this->SUT->parse($this->mockCallable);

        $this->assertFalse($this->SUT->shouldUseReplica());
    }
}
