<?php

namespace CJCodes\SlaveRouteLimiterBundle\Util;

use Doctrine\Common\Annotations\Reader;
use CJCodes\SlaveRouteLimiterBundle\Annotation\ShouldUseSlave;
use CJCodes\SlaveRouteLimiterBundle\Annotation\ShouldNotUseSlave;

class Configurations
{
    /**
     * @var string
     */
    const ANNOTATION_CLASS = ShouldUseSlave::class;

    /**
     * @var string
     */
    const NEGATION_CLASS = ShouldNotUseSlave::class;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var ShouldUseSlave
     */
    public $class;

    /**
     * @var ShouldUseSlave
     */
    public $method;

    /**
     * @var ShouldNotUseSlave
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

    public function shouldUseSlave()
    {
        return !is_null($this->method) || (!is_null($this->class) && is_null($this->negation));
    }
}
