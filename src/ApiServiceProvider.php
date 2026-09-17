<?php

namespace Bale\Api;

use Bale\Api\Commands\InstallApiCommand;
use Bale\Api\Commands\PruneExpiredTokensCommand;
use Bale\Api\Commands\PublishMigrationCommand;
use Bale\Api\Guards\ApiTokenGuard;
use Bale\Api\Middleware\AuthenticateApiToken;
use Bale\Api\Middleware\EnsureApiHardening;
use Bale\Api\Middleware\VerifyApiScope;
use Bale\Api\Services\ApiCatalog;
use Bale\Api\Services\ApiScopeRegistry;
use Bale\Api\Services\TokenManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Component as LivewireComponent;
use Livewire\Livewire;
use Symfony\Component\Finder\Finder;

class ApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/api.php', 'api');

        $this->app->singleton(TokenManager::class);
        $this->app->singleton(ApiScopeRegistry::class);
        $this->app->singleton(ApiCatalog::class);

        $this->app->alias(TokenManager::class, 'api.tokens');
        $this->app->alias(ApiScopeRegistry::class, 'api.scopes');

        $this->registerApiTokenGuard();
        $this->registerCommands();
    }

    protected function registerApiTokenGuard(): void
    {
        if (! config()->has('auth.guards.api-token')) {
            config()->set('auth.guards.api-token', [
                'driver' => 'api-token',
            ]);
        }
    }

    protected function registerCommands(): void
    {
        $commands = [
            'command.api:publish-migration' => PublishMigrationCommand::class,
            'command.api:install' => InstallApiCommand::class,
            'command.api:prune-expired' => PruneExpiredTokensCommand::class,
        ];

        foreach ($commands as $key => $class) {
            $this->app->bind($key, $class);
        }

        $this->commands(array_keys($commands));
    }

    public function boot(): void
    {
        require_once __DIR__.'/helpers.php';

        $router = $this->app['router'];
        $router->aliasMiddleware('api.token', AuthenticateApiToken::class);
        $router->aliasMiddleware('api.hardening', EnsureApiHardening::class);
        $router->aliasMiddleware('scope', VerifyApiScope::class);
        $router->aliasMiddleware('api.ability', VerifyApiScope::class);

        // Middleware stack bersama untuk seluruh endpoint API package.
        // Package domain cukup memakai: Route::middleware('bale.api').
        $router->middlewareGroup('bale.api', [
            'throttle:api',
            AuthenticateApiToken::class,
            EnsureApiHardening::class,
            SubstituteBindings::class,
        ]);

        Auth::extend('api-token', function ($app, $name, array $config) {
            return new ApiTokenGuard($app['request'], $app->make(TokenManager::class));
        });

        RateLimiter::for('api', function () {
            return Limit::perMinute(config('api.throttle.per_minute', 60));
        });

        $this->app->make(ApiScopeRegistry::class)->register('core', [
            'api.read' => 'Akses dasar API (health check & pemeriksaan scope).',
        ]);

        $this->app->booted(function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        });

        $this->loadMigrations();
        $this->registerViews();
        $this->offerPublishing();
        $this->registerLivewireComponents();
    }

    /**
     * Load migration langsung dari package.
     *
     * Migration disimpan sebagai `.stub` (publishable), sehingga pemanggilan
     * disini hanya efektif untuk file `.php` — disediakan agar inline package
     * migration tetap didukung (pola rakaca).
     */
    protected function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(
            __DIR__.'/../resources/views',
            'api'
        );
    }

    protected function offerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/api.php' => config_path('api.php'),
        ], 'api:config');

        $this->publishes($this->getMigrations(), 'api:migrations');
    }

    protected function getMigrations(): array
    {
        $migrations = [];
        $sourcePath = __DIR__.'/../database/migrations/';

        if (! is_dir($sourcePath)) {
            return $migrations;
        }

        foreach (glob($sourcePath.'*.{php,stub}', GLOB_BRACE) as $file) {
            $filename = basename($file);

            $targetFile = $this->getMigrationFileName($filename);

            $migrations[$file] = $targetFile;
        }

        return $migrations;
    }

    protected function getMigrationFileName(string $filename): string
    {
        $timestamp = date('Y_m_d_His');
        $migrationName = str_replace('.php.stub', '.php', $filename);

        return database_path('migrations/'.$timestamp.'_'.$migrationName);
    }

    protected function registerLivewireComponents(): void
    {
        $namespace = 'Bale\\Api\\Livewire';
        $basePath = __DIR__.'/Livewire';

        if (! is_dir($basePath)) {
            return;
        }

        $finder = new Finder;
        $finder->files()->in($basePath)->name('*.php');

        foreach ($finder as $file) {
            $relativePathname = $file->getRelativePathname();

            $nsPath = str_replace(['/', '\\'], '\\', $relativePathname);

            $class = $namespace.'\\'.Str::beforeLast($nsPath, '.php');

            if (! class_exists($class)) {
                continue;
            }

            if (! is_subclass_of($class, LivewireComponent::class)) {
                continue;
            }

            $withoutExt = Str::replaceLast('.php', '', $relativePathname);
            $segments = preg_split('#[\\/\\\\]#', $withoutExt);
            $kebab = array_map(fn ($s) => Str::kebab($s), $segments);

            $alias = 'api.'.implode('.', $kebab);

            Livewire::component($alias, $class);
        }
    }
}
