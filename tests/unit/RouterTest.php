<?php

declare(strict_types=1);

namespace Archict\Router;

use Archict\Core\Core;
use Archict\Core\Services\ServiceManager;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private ServiceManager $service_manager;
    private Router $router;

    protected function setUp(): void
    {
        $core = Core::build();
        $core->load();
        $this->service_manager = $core->service_manager;

        $router = $this->service_manager->get(Router::class);
        self::assertNotNull($router);
        $this->router = $router;
    }

    public function testRouterIsLoaded(): void
    {
        self::assertTrue($this->service_manager->has(Router::class));
        self::assertInstanceOf(Router::class, $this->service_manager->get(Router::class));
    }

    public function testItReturns404(): void
    {
        $response = $this->router->route(new ServerRequest('GET', 'route'));
        self::assertSame(404, $response->getStatusCode());
    }
}
