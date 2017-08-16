<?php

namespace CJCodes\SlaveRouteLimiterBundle\EventListener;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\KernelEvents;
use Doctrine\DBAL\Connections\MasterSlaveConnection;
use CJCodes\SlaveRouteLimiterBundle\Util\Configurations;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
        // If we don't have a MasterSlaveConnection, we're safe.
        if (!($this->connection instanceof MasterSlaveConnection)) {
            return;
        }

        $controllerCallable = $event->getController();

        // If we don't have a controller route
        if (!is_array($controllerCallable)) {
            $this->setMaster();
        } else {
            $this->configurations->parse($controllerCallable);

            if ($this->configurations->shouldUseSlave()) {
                $this->setSlave();
            } else {
                $this->setMaster();
            }
        }
    }

    protected function setMaster()
    {
        $this->connection->connect('master');
    }

    protected function setSlave()
    {
        // Nothing needs to happen here, because the MasterSlaveConnection
        // will automatically pick a random slave. For the sake of clarity,
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
