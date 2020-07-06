<?php

namespace CrisisTextLine\ReadReplicaRouteLimiterBundle\Annotation;

/**
 * @Annotation
 */
class ShouldUseReplica
{
    public function __construct($options = [])
    {
        if (count($options) > 0) {
            throw new \InvalidArgumentException('@ShouldUseReplica annotation does not accept any parameters.');
        }
    }
}
