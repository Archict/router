<?php

declare(strict_types=1);

namespace Archict\Router;

use Archict\Core\Core;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testRouterIsLoaded(): void
    {
        $core = Core::build();
        $core->load();
        self::assertTrue($core->service_manager->has(Router::class));
        self::assertInstanceOf(Router::class, $core->service_manager->get(Router::class));
    }
}
