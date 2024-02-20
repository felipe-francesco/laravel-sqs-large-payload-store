<?php

namespace FelipeFrancesco\LaravelSQSLargePayload\Queue;

use Aws\Sqs\SqsClient;
use FelipeFrancesco\LaravelSQSLargePayload\Exceptions\LaravelSQSLargePayloadException;
use FelipeFrancesco\LaravelSQSLargePayload\Queue\Jobs\JobFactory;
use Illuminate\Contracts\Queue\ClearableQueue;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\InvalidPayloadException;
use Illuminate\Queue\SqsQueue;

class SqsStorageQueue extends SqsQueue implements QueueContract, ClearableQueue
{
    private $object_max_size = 262144;

    /**
     * Create a new Amazon SQS queue instance.
     *
     * @param  \Aws\Sqs\SqsClient  $sqs
     * @param  string  $default
     * @param  string  $prefix
     * @param  string  $suffix
     * @param  bool  $dispatchAfterCommit
     * @param  string $storage
     * @return void
     */
    /**
     * @infection-ignore-all
     */      
    public function __construct(SqsClient $sqs,
                                private $repository,
                                $default,
                                $prefix = '',
                                $suffix = '',
                                $dispatchAfterCommit = false,
                                private JobFactory $jobFactory = new JobFactory()
                                )
    {
        $this->object_max_size = config("sqs-storage-queue.max_size") ?? 262144;
        parent::__construct($sqs, $default, $prefix, $suffix, $dispatchAfterCommit);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string|null  $queue
     * @param  array  $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        if(byteLength($payload) > $this->object_max_size)
        {
            return $this->sendMessageStoringOnStorage($payload, $queue, $options);
        }
        return parent::pushRaw($payload, $queue, $options);
    }

    private function sendMessageStoringOnStorage($payload, $queue, array $options = [])
    {
        try {
            $jobData = json_decode($payload);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new InvalidPayloadException(
                    'Unable to JSON encode payload. Error code: '.json_last_error()
                );
            }
            if(!isset($jobData->uuid)) {
                return parent::pushRaw($payload, $queue, $options);
            }
            $this->repository->saveDataOnStorage($jobData->uuid, $jobData);

        } catch(LaravelSQSLargePayloadException $e) {
            return parent::pushRaw($payload, $queue, $options);
        }

        $jobData->isSqsStorage = true;
        $jobData->data = [];

        $payload = json_encode($jobData, \JSON_UNESCAPED_UNICODE);
        return $this->sqs->sendMessage([
            'QueueUrl' => $this->getQueue($queue), 'MessageBody' => $payload,
        ])->get('MessageId');
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string|null  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $queue = $this->getQueue($queue),
            'AttributeNames' => ['ApproximateReceiveCount'],
        ]);
        if (! is_null($response['Messages']) && count($response['Messages']) > 0) {
            return $this->jobFactory->create(
                $this->container, $this->sqs, $response['Messages'][0],
                $this->connectionName, $queue, $this->repository
            );
        }
    }
}
