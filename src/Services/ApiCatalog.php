<?php

namespace Bale\Api\Services;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * Katalog referensi API.
 *
 * Dibangun saat runtime dari tabel route + {@see ApiScopeRegistry}, sehingga
 * yang tampil di dokumentasi selalu sama persis dengan yang ditegakkan server.
 */
class ApiCatalog
{
    /**
     * Middleware yang menandai sebuah route sebagai bagian dari area API.
     */
    protected const API_MARKERS = [
        'bale.api',
        'api.token',
        'api.hardening',
    ];

    public function __construct(protected ApiScopeRegistry $scopes) {}

    /**
     * Route API dikelompokkan per domain (segmen pertama setelah `api/`).
     *
     * @return array<string, list<array{methods: list<string>, uri: string, scopes: list<string>, action: string, name: ?string}>>
     */
    public function grouped(): array
    {
        $domains = [];

        foreach (RouteFacade::getRoutes() as $route) {
            if (! $this->isApiRoute($route)) {
                continue;
            }

            $scopes = $this->scopesFor($route);
            $domain = $this->domainFor($route);

            $domains[$domain][] = [
                'methods' => array_values(array_filter(
                    $route->methods(),
                    fn (string $method) => $method !== 'HEAD',
                )),
                'uri' => $route->uri(),
                'scopes' => $scopes,
                'action' => $route->getActionName(),
                'name' => $route->getName(),
            ];
        }

        ksort($domains);

        return $domains;
    }

    /**
     * Scope terdaftar lengkap dengan deskripsi.
     *
     * @return array<string, list<array{name: string, description: string}>>
     */
    public function scopes(): array
    {
        return $this->scopes->scopes();
    }

    /**
     * @return array{endpoints: int, domains: int, scopes: int}
     */
    public function stats(): array
    {
        $grouped = $this->grouped();

        $endpoints = 0;

        foreach ($grouped as $routes) {
            $endpoints += count($routes);
        }

        return [
            'endpoints' => $endpoints,
            'domains' => count($grouped),
            'scopes' => count($this->scopes->flat()),
        ];
    }

    /**
     * Deteksi ketidakcocokan antara scope yang dipakai route dengan yang terdaftar.
     *
     * - `unregistered`: dipakai route tapi belum terdaftar (endpoint terkunci).
     * - `unused`: terdaftar tapi tidak dipakai route mana pun (kemungkinan lupa pasang).
     *
     * @return array{unregistered: list<string>, unused: list<string>}
     */
    public function scopeMismatches(): array
    {
        $registered = $this->scopes->flat();
        $used = [];

        foreach (RouteFacade::getRoutes() as $route) {
            if (! $this->isApiRoute($route)) {
                continue;
            }

            foreach ($this->scopesFor($route) as $scope) {
                $used[] = $scope;
            }
        }

        $used = array_values(array_unique($used));

        return [
            'unregistered' => array_values(array_diff($used, $registered)),
            'unused' => array_values(array_diff($registered, $used)),
        ];
    }

    protected function isApiRoute(Route $route): bool
    {
        if (str_starts_with($route->uri(), 'api/') || $route->uri() === 'api') {
            return true;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            if (in_array($middleware, self::API_MARKERS, true) || str_starts_with($middleware, 'scope:')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    protected function scopesFor(Route $route): array
    {
        $scopes = [];

        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            if (preg_match('/^(?:scope|api\.ability):(.+)$/', $middleware, $matches) !== 1) {
                continue;
            }

            foreach (explode(',', $matches[1]) as $scope) {
                $scope = trim($scope);

                if ($scope !== '') {
                    $scopes[] = $scope;
                }
            }
        }

        return array_values(array_unique($scopes));
    }

    protected function domainFor(Route $route): string
    {
        $segments = explode('/', $route->uri());

        if (($segments[0] ?? null) === 'api') {
            return $segments[1] ?? 'core';
        }

        return 'core';
    }
}
