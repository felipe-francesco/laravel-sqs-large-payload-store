<?php

namespace FelipeFrancesco\LaravelSQSLargePayload\Queue\Jobs;

use Aws\Sqs\SqsClient;
use Exception;
use FelipeFrancesco\LaravelSQSLargePayload\Repository\StorageRepository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\JobName;
use Illuminate\Queue\Jobs\SqsJob;

class SqsStorageJob extends SqsJob implements JobContract
{
    private $rawPayload = null;

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
    public function __construct(
        Container $container, 
        SqsClient $sqs, 
        array $job, 
        $connectionName, 
        $queue, 
        private StorageRepository $repository
    ) {
        parent::__construct($container, $sqs, $job, $connectionName, $queue);
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        if(is_null($this->rawPayload))
        {
            $jobData = json_decode($this->job["Body"]);

            if(isset($jobData->isSqsStorage) && $jobData->isSqsStorage === true) {
                if(!$this->repository->isValid()) {
                    throw new Exception("Invalid storage.");
                }
                $this->rawPayload = $this->repository->getDataFromStorage($jobData->uuid);
                $this->repository->removeFromStorage($jobData->uuid);
            }
        }

        if(!is_null($this->rawPayload)) {
            return $this->rawPayload;
        }

        return parent::getRawBody();
    }

    /**
     * Process an exception that caused the job to fail.
     *
     * @param  \Throwable|null  $e
     * @return void
     */
    /**
     * @infection-ignore-all
     */    
    protected function failed($e)
    {
        $payload = $this->payload();

        if(is_null($payload)) {
            return;
        }

        [$class, $method] = JobName::parse($payload['job']);

        if (method_exists($this->instance = $this->resolve($class), 'failed')) {
            $this->instance->failed($payload['data'], $e, $payload['uuid'] ?? '');
        }
    }
}
