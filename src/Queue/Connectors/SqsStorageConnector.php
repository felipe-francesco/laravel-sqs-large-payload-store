<?php

namespace FelipeFrancesco\LaravelSQSLargePayload\Queue\Connectors;

use Aws\Sqs\SqsClient;
use FelipeFrancesco\LaravelSQSLargePayload\Queue\SqsStorageQueue;
use FelipeFrancesco\LaravelSQSLargePayload\Repository\StorageRepository;
use Illuminate\Queue\Connectors\ConnectorInterface;
use Illuminate\Queue\Connectors\SqsConnector;
use Illuminate\Support\Arr;

/**
 * @infection-ignore-all
 */      
class SqsStorageConnector extends SqsConnector implements ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $config = $this->getDefaultConfiguration($config);
        if (! empty($config['key']) && ! empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
        }

        return new SqsStorageQueue(
            new SqsClient($config),
            new StorageRepository(
                $config['storage'] ?? config('sqs-storage-queue.storage'), 
                config("sqs-storage-queue.prefix", "jobs")
            ),
            $config['queue'],
            $config['prefix'] ?? '',
            $config['suffix'] ?? '',
            $config['after_commit'] ?? null
        );
    }
}
