<?php

namespace ArsamMe\Wallet\Services;

use ArsamMe\Wallet\Contracts\Events\EventInterface;
use ArsamMe\Wallet\Contracts\Services\DatabaseServiceInterface;
use ArsamMe\Wallet\Contracts\Services\DispatcherServiceInterface;
use Illuminate\Contracts\Events\Dispatcher;

final class DispatcherService implements DispatcherServiceInterface {
    /**
     * @var array<string, bool>
     */
    private array $events = [];

    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly DatabaseServiceInterface $databaseService
    ) {}

    public function dispatch(EventInterface $event): void {
        $this->events[$event::class] = true;
        $this->dispatcher->push($event::class, [$event]);
    }

    public function flush(): void {
        foreach (array_keys($this->events) as $event) {
            $this->dispatcher->flush($event);
        }

        $this->dispatcher->forgetPushed();
        $this->events = [];
    }

    public function forgot(): void {
        foreach (array_keys($this->events) as $event) {
            $this->dispatcher->forget($event);
        }

        $this->events = [];
    }

    public function lazyFlush(): void {
        if ($this->databaseService->getConnection()->transactionLevel() === 0) {
            $this->flush();
        }
    }
}
