<?php

declare(strict_types=1);

namespace FelipeFrancesco\LaravelSQSLargePayload\Providers;

use FelipeFrancesco\LaravelSQSLargePayload\Queue\Connectors\SqsStorageConnector;
use Illuminate\Support\ServiceProvider;

final class LaravelQueueToStorageDriver extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->afterResolving('queue', function ($manager) {
            $manager->addConnector('sqs-large', function () {
                return new SqsStorageConnector;
            });
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    /**
     * @infection-ignore-all
     */    
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/sqs-storage-queue.php' => config_path('sqs-storage-queue.php')
        ]);
    }
}
