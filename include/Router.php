<?php

declare(strict_types=1);

namespace Archict\Router;

use Archict\Brick\Service;
use Archict\Core\Event\EventDispatcher;

#[Service]
final readonly class Router
{
    public function __construct(
        private EventDispatcher $event_dispatcher,
    ) {
    }

    public function route(): void
    {
        $this->collectRoutes();
    }

    private function collectRoutes(): void
    {
        $collector         = $this->event_dispatcher->dispatch(new RouteCollectorEvent());
        $_collected_routes = $collector->getCollectedRoutes();
    }
}
