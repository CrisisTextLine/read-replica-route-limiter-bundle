<?php

namespace CrisisTextLine\ReadReplicaRouteLimiterBundle\Util;

use CrisisTextLine\ReadReplicaRouteLimiterBundle\Annotation\ShouldNotUseReplica;
use CrisisTextLine\ReadReplicaRouteLimiterBundle\Annotation\ShouldUseReplica;
use Doctrine\Common\Annotations\Reader;

class Configurations
{
    /**
     * @var string
     */
    const ANNOTATION_CLASS = ShouldUseReplica::class;

    /**
     * @var string
     */
    const NEGATION_CLASS = ShouldNotUseReplica::class;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var ShouldUseReplica
     */
    public $class;

    /**
     * @var ShouldUseReplica
     */
    public $method;

    /**
     * @var ShouldNotUseReplica
     */
    public $negation;

    /**
     * Constructor.
     *
     * @param Reader $reader A Reader instance
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function parse($controllerCallable)
    {
        $object = new \ReflectionClass(get_class($controllerCallable[0]));
        $method = $object->getMethod($controllerCallable[1]);

        $this->class = $this->reader->getClassAnnotation($object, self::ANNOTATION_CLASS);
        $this->method = $this->reader->getMethodAnnotation($method, self::ANNOTATION_CLASS);
        $this->negation = $this->reader->getMethodAnnotation($method, self::NEGATION_CLASS);
    }

    public function shouldUseReplica()
    {
        return !is_null($this->method) || (!is_null($this->class) && is_null($this->negation));
    }
}
