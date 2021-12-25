<?php

declare(strict_types=1);

namespace Napp\Xray\Collectors;

abstract class EventsCollector extends SegmentCollector
{
    /**
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    public function __construct()
    {
        $this->app = app();

        $this->registerEventListeners();
    }
}
