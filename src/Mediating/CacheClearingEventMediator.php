<?php

namespace Serenata\Mediating;

use Evenement\EventEmitterInterface;

use Serenata\Analysis\ClearableCacheInterface;

/**
 * Mediator that invokes a cache clear when an event happens.
 *
 * This class effectively mediates between objects emitting events that should trigger a cache clear and objects that
 * handle clearing the cache.
 */
final class CacheClearingEventMediator
{
    /**
     * @var ClearableCacheInterface
     */
    private $clearableCache;

    /**
     * @var EventEmitterInterface
     */
    private $eventEmitter;

    /**
     * @var string
     */
    private $eventName;

    /**
     * @param ClearableCacheInterface $clearableCache
     * @param EventEmitterInterface   $eventEmitter
     * @param string                  $eventName
     */
    public function __construct(
        ClearableCacheInterface $clearableCache,
        EventEmitterInterface $eventEmitter,
        string $eventName
    ) {
        $this->clearableCache = $clearableCache;
        $this->eventEmitter = $eventEmitter;
        $this->eventName = $eventName;

        $this->setup();
    }

    /**
     * @return void
     */
    private function setup(): void
    {
        $this->eventEmitter->on($this->eventName, function (): void {
            $this->clearCache();
        });
    }

    /**
     * @return void
     */
    private function clearCache(): void
    {
        $this->clearableCache->clearCache();
    }
}
