<?php

declare(strict_types=1);

namespace Napp\Xray;

use Illuminate\Support\ServiceProvider;
use Napp\Xray\Collectors\FrameworkCollector;
use Napp\Xray\Collectors\RouteCollector;

class XrayLumenBootedServiceProvider extends ServiceProvider
{

    /**
     * Booting of services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('xray.framework')) {
            app(FrameworkCollector::class)->registerFrameworkBootedEvent();
        }
    }
}
