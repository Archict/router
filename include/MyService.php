<?php

declare(strict_types=1);

namespace Archict\Router;

use Archict\Brick\ListeningEvent;
use Archict\Brick\Service;

#[Service(MyConfiguration::class, 'foo.yml')]
final readonly class MyService
{
    public function __construct(
        private MyConfiguration $configuration,
    ) {
    }

    public function getConfiguration(): MyConfiguration
    {
        return $this->configuration;
    }

    #[ListeningEvent]
    public function listenToMyEvent(MyEvent $event): void
    {
        $event->has_been_called = true;
    }
}
