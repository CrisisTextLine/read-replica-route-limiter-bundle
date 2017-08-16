<?php

namespace CJCodes\SlaveRouteLimiterBundle\Annotation;

/**
 * @Annotation
 */
class ShouldNotUseSlave
{
    public function __construct($options = [])
    {
        if (count($options) > 0) {
            throw new \InvalidArgumentException('@ShouldNotUseSlave annotation does not accept any parameters.');
        }
    }
}
