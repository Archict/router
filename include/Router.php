<?php

declare(strict_types=1);

namespace Archict\Router;

use Archict\Brick\Service;
use Archict\Core\Event\EventDispatcher;
use Archict\Router\Route\RouteCollection;
use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\TreeMapper;
use CuyZ\Valinor\MapperBuilder;
use CuyZ\Valinor\Normalizer\Format;
use CuyZ\Valinor\Normalizer\Normalizer;
use Psr\SimpleCache\CacheInterface;

#[Service]
final class Router
{
    private const CACHE_KEY = 'archict/router->route_collection';
    private readonly TreeMapper $mapper;
    private readonly Normalizer $normalizer;
    private RouteCollection $route_collection;

    public function __construct(
        private readonly EventDispatcher $event_dispatcher,
        private readonly CacheInterface $cache,
    ) {
        $this->mapper     = (new MapperBuilder())->allowPermissiveTypes()->mapper();
        $this->normalizer = (new MapperBuilder())->normalizer(Format::json());
    }

    public function route(): void
    {
        $this->loadRoutes();
    }

    private function loadRoutes(): void
    {
        if ($this->cache->has(self::CACHE_KEY)) {
            $cache_value = $this->cache->get(self::CACHE_KEY);
            try {
                $this->route_collection = $this->mapper->map(RouteCollection::class, $cache_value);
            } catch (MappingError) {
                $this->collectRoutes();
            }
        } else {
            $this->collectRoutes();
        }
    }

    private function collectRoutes(): void
    {
        $this->route_collection = new RouteCollection();

        $collector         = $this->event_dispatcher->dispatch(new RouteCollectorEvent());
        $_collected_routes = $collector->getCollectedRoutes();

        // TODO: check route not already defined in collection then add it in it

        $cache_value = $this->normalizer->normalize($this->route_collection);
        $this->cache->set(self::CACHE_KEY, $cache_value);
    }
}
