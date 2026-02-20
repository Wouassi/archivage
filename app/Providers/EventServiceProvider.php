<?php

namespace App\Providers;

use App\Events\DocumentArchived;
use App\Listeners\SyncDocumentToCloud;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        DocumentArchived::class => [
            SyncDocumentToCloud::class,
        ],
    ];

    public function boot(): void {}
}
