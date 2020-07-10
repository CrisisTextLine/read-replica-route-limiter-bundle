<?php

namespace CrisisTextLine\ReadReplicaRouteLimiterBundle\EventListener;

use CrisisTextLine\ReadReplicaRouteLimiterBundle\Util\Configurations;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connections\MasterSlaveConnection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ControllerListener implements EventSubscriberInterface
{
    /**
     * @var Configurations
     */
    protected $configurations;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * Constructor.
     *
     * @param Configurations $configurations
     */
    public function __construct(Configurations $configurations, Connection $connection)
    {
        $this->configurations = $configurations;
        $this->connection = $connection;
    }

    /**
     * @param  FilterControllerEvent $event [description]
     * @return [type]                       [description]
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        // If we don't have a MasterSlaveConnection, there's no need to intercept the request
        if (!($this->connection instanceof MasterSlaveConnection)) {
            return;
        }

        $controllerCallable = $event->getController();

        // If we don't have a controller route
        if (!is_array($controllerCallable)) {
            $this->setPrimary();
        } else {
            $this->configurations->parse($controllerCallable);

            if ($this->configurations->shouldUseReplica()) {
                $this->setReplica();
            } else {
                $this->setPrimary();
            }
        }
    }

    protected function setPrimary()
    {
        $this->connection->connect('master');
    }

    protected function setReplica()
    {
        // Nothing needs to happen here, because the MasterSlaveConnection
        // will automatically pick a random replica. For the sake of clarity,
        // we're going to call $connection->connect('slave') anyway.
        $this->connection->connect('slave');
    }

    /**
     * Return subscribed events.
     *
     * @return array The events this service subscribes to.
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::CONTROLLER => 'onKernelController',
        );
    }
}
