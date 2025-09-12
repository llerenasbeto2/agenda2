<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AdministradorGeneralMiddleware;
use App\Http\Middleware\AdministradorEstatalMiddleware;
use App\Http\Middleware\AdministradorAreaMiddleware;
use App\Http\Middleware\UsuarioMiddleware;

class MiddlewareServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
        // Registrar los middlewares en el router
        $router = $this->app['router'];

        $router->aliasMiddleware('admin', AdminMiddleware::class);
        $router->aliasMiddleware('admin.general', AdministradorGeneralMiddleware::class);
        $router->aliasMiddleware('admin.estatal', AdministradorEstatalMiddleware::class);
        $router->aliasMiddleware('admin.area', AdministradorAreaMiddleware::class);
        $router->aliasMiddleware('usuario', UsuarioMiddleware::class);
    }
}
