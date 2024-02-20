<?php

namespace FelipeFrancesco\LaravelSQSLargePayload\Queue\Jobs;

use Aws\Sqs\SqsClient;
use FelipeFrancesco\LaravelSQSLargePayload\Queue\Jobs\SqsStorageJob;
use FelipeFrancesco\LaravelSQSLargePayload\Repository\StorageRepository;
use Illuminate\Container\Container;

/**
 * @infection-ignore-all
 */
class JobFactory
{
/**
     * Create a new job instance.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  \Aws\Sqs\SqsClient  $sqs
     * @param  array  $job
     * @param  string  $connectionName
     * @param  string  $queue
     * @param  StorageRepository $repository
     * @return void
     */    
    public function create(
        Container $container, 
        SqsClient $sqs, 
        array $job, 
        $connectionName, 
        $queue,
        StorageRepository $repository 
    ) {
        return new SqsStorageJob(
            $container, $sqs, $job, $connectionName, $queue, $repository
        );
    }
}
