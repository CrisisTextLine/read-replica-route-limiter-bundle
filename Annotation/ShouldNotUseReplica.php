<?php

namespace CrisisTextLine\ReadReplicaRouteLimiterBundle\Annotation;

/**
 * @Annotation
 */
class ShouldNotUseReplica
{
    public function __construct($options = [])
    {
        if (count($options) > 0) {
            throw new \InvalidArgumentException('@ShouldNotUseReplica annotation does not accept any parameters.');
        }
    }
}
