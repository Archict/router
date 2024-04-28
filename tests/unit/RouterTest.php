<?php

declare(strict_types=1);

namespace Archict\Router;

use Archict\Core\Core;
use Archict\Core\Services\ServiceManager;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private ServiceManager $service_manager;

    protected function setUp(): void
    {
        $core = Core::build();
        $core->load();
        $this->service_manager = $core->service_manager;
    }

    public function testRouterIsLoaded(): void
    {
        self::assertTrue($this->service_manager->has(Router::class));
        self::assertInstanceOf(Router::class, $this->service_manager->get(Router::class));
    }

    public function testItNotThrow(): void
    {
        $router = $this->service_manager->get(Router::class);
        self::assertNotNull($router);
        $router->route();
    }
}
