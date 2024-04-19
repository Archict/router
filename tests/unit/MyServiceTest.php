<?php

declare(strict_types=1);

namespace Archict\BrickTemplate;

use Archict\Core\Core;
use Archict\Core\Event\EventDispatcher;
use Archict\Core\Services\ServiceManager;
use PHPUnit\Framework\TestCase;

final class MyServiceTest extends TestCase
{
    private ServiceManager $service_manager;

    protected function setUp(): void
    {
        $core = Core::build();
        $core->load();
        $this->service_manager = $core->service_manager;
    }

    public function testMyServiceIsLoaded(): void
    {
        self::assertTrue($this->service_manager->has(MyService::class));
        self::assertInstanceOf(MyService::class, $this->service_manager->get(MyService::class));
    }

    public function testMyServiceListenMyEvent(): void
    {
        $dispatcher = $this->service_manager->get(EventDispatcher::class);
        self::assertNotNull($dispatcher);
        $event = $dispatcher->dispatch(new MyEvent(false));
        self::assertInstanceOf(MyEvent::class, $event);
        self::assertTrue($event->has_been_called);
    }

    public function testConfigurationIsLoaded(): void
    {
        $service = $this->service_manager->get(MyService::class);
        self::assertInstanceOf(MyService::class, $service);
        self::assertSame('hello', $service->getConfiguration()->name);
    }
}
