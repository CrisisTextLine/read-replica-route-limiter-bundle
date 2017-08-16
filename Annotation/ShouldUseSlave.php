<?php

namespace CJCodes\SlaveRouteLimiterBundle\Annotation;

/**
 * @Annotation
 */
class ShouldUseSlave
{
    public function __construct($options = [])
    {
        if (count($options) > 0) {
            throw new \InvalidArgumentException('@ShouldUseSlave annotation does not accept any parameters.');
        }
    }
}
