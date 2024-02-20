<?php

namespace FelipeFrancesco\LaravelSQSLargePayload\Tests\Unit\Queue;

use Aws\Sqs\SqsClient;
use Faker\Factory;
use FelipeFrancesco\LaravelSQSLargePayload\Queue\Jobs\JobFactory;
use FelipeFrancesco\LaravelSQSLargePayload\Queue\Jobs\SqsStorageJob;
use FelipeFrancesco\LaravelSQSLargePayload\Queue\SqsStorageQueue;
use FelipeFrancesco\LaravelSQSLargePayload\Repository\StorageRepository;
use FelipeFrancesco\LaravelSQSLargePayload\Tests\stdObject;
use FelipeFrancesco\LaravelSQSLargePayload\Tests\TestBase;
use Illuminate\Container\Container;
use Illuminate\Queue\InvalidPayloadException;
use Mockery;

final class SqsStorageQueueTest extends TestBase
{
    private $faker;

    protected function setUp(): void
    {
        $this->faker = Factory::create();
        parent::setUp();
    }

    protected function tearDown() : void
    {
        Mockery::close();
    }

    private function getOverflowMessageText($offset = 0) {
        return str_repeat("A", config("sqs-storage-queue.max_size", 262144)-$offset+1);
    }

    private function getOverflowBodyMessage(array $additionalData, bool $smaller = false) {
        $messageBody = json_encode(array_merge(["data" => ""], $additionalData));
        $newBody = json_decode($messageBody);
        $newBody->data = $this->getOverflowMessageText(byteLength($messageBody) + ($smaller ? 1 : 0));
        return json_encode($newBody);
    }

    public function testItStoresOnSqsWithoutOverflowingMessageSize() {
        $queueName = $this->faker->slug;
        $messageId = $this->faker->uuid;
        $messageBody = $this->getOverflowBodyMessage(["uuid" => $messageId], smaller: true);
        $mockSqs = Mockery::mock(SqsClient::class)->makePartial();
        $message = new stdObject(["MessageId" => $messageId, "uuid" => $this->faker->uuid]);
        $message->{"get"} = fn ($ref, $param) => $ref->{$param};
        $mockSqs->shouldReceive("sendMessage")
            ->once()->with([
                "QueueUrl" => "/". $queueName,
                "MessageBody" => $messageBody
            ])->andReturn($message);
        $queue = new SqsStorageQueue(
            $mockSqs,
            Mockery::mock(StorageRepository::class),
            $queueName
        );
        $this->assertEquals($messageId, $queue->pushRaw($messageBody));
    }

    public function testItStoresOnStorageWhenOverflowsMessageSize() {
        $queueName = $this->faker->slug;
        $messageId = $this->faker->uuid;
        $messageBody = $this->getOverflowBodyMessage([
            "uuid" => $messageId
        ]);
        $repository = Mockery::mock(StorageRepository::class);
        $repository->shouldReceive("saveDataOnStorage")->once()
            ->andReturnUsing(function ($jobId, $payload) use($messageId, $messageBody) {
                $this->assertEquals($messageId, $jobId);
                $this->assertEquals($payload, json_decode($messageBody));
                return true;
            });

        $message = new stdObject(["MessageId" => $messageId]);
        $message->{"get"} = fn ($ref, $param) => $ref->{$param};
        $mockSqs = Mockery::mock(SqsClient::class)->makePartial();
        $mockSqs->shouldReceive("sendMessage")
            ->once()->with([
                "QueueUrl" => "/". $queueName,
                "MessageBody" => json_encode(
                    ["data" => [], "uuid" => $messageId, "isSqsStorage" => true], 
                    \JSON_UNESCAPED_UNICODE
                )
            ])->andReturn($message);
        $queue = new SqsStorageQueue(
            $mockSqs,
            $repository,
            $queueName
        );
        $this->assertEquals($messageId, $queue->pushRaw($messageBody));        
    }

    public function testItThrowsExceptionWhenOverflowsButNotJson() {
        $queueName = $this->faker->slug;
        $messageId = $this->faker->uuid;
        $messageBody = str_repeat("A", config("sqs-storage-queue.max_size", 262144)+1);

        $repository = Mockery::mock(StorageRepository::class);
        $repository->shouldNotReceive("saveDataOnStorage");

        $mockSqs = Mockery::mock(SqsClient::class)->makePartial();
        $mockSqs->shouldNotReceive("sendMessage");

        $this->expectException(InvalidPayloadException::class);
        $this->expectExceptionMessage("Unable to JSON encode payload. Error code: 4");
        $queue = new SqsStorageQueue(
            $mockSqs,
            $repository,
            $queueName
        );
        $this->assertEquals($messageId, $queue->pushRaw($messageBody));        
    }

    public function testItStoresOnSqsWhenOVerflowsButNoId() {
        $queueName = $this->faker->slug;
        $messageId = $this->faker->uuid;
        $messageBody = $this->getOverflowBodyMessage([]);

        $repository = Mockery::mock(StorageRepository::class);
        $repository->shouldNotReceive("saveDataOnStorage");

        $mockSqs = Mockery::mock(SqsClient::class)->makePartial();
        $message = new stdObject(["MessageId" => $messageId, "uuid" => $this->faker->uuid]);
        $message->{"get"} = fn ($ref, $param) => $ref->{$param};
        $mockSqs->shouldReceive("sendMessage")
            ->once()->with([
                "QueueUrl" => "/". $queueName,
                "MessageBody" => $messageBody
            ])->andReturn($message);
        $queue = new SqsStorageQueue(
            $mockSqs,
            $repository,
            $queueName
        );
        $this->assertEquals($messageId, $queue->pushRaw($messageBody));        
    }

    public function testItReturnsNullWhenPopReceivesMessagesIsNull() {
        $queueName = $this->faker->slug;
        $mockSqs = Mockery::mock(SqsClient::class)->makePartial();
        $mockSqs->shouldReceive("receiveMessage")
            ->once()->with([
                "QueueUrl" => "/". $queueName,
                'AttributeNames' => ['ApproximateReceiveCount'],
            ])->andReturn(["Messages" => null]);
        $queue = new SqsStorageQueue(
            $mockSqs,
            Mockery::mock(StorageRepository::class),
            $queueName
        );
        $this->assertNull($queue->pop());
    }
    
    public function testItReturnsNullWhenPopReceivesMessagesIsEmpty() {
        $queueName = $this->faker->slug;
        $mockSqs = Mockery::mock(SqsClient::class)->makePartial();
        $mockSqs->shouldReceive("receiveMessage")
            ->once()->with([
                "QueueUrl" => "/". $queueName,
                'AttributeNames' => ['ApproximateReceiveCount'],
            ])->andReturn(["Messages" => []]);
        $queue = new SqsStorageQueue(
            $mockSqs,
            Mockery::mock(StorageRepository::class),
            $queueName
        );
        $this->assertNull($queue->pop());
    }

    public function testItReturnsJobWhenReceivesMessages() {
        $queueName = $this->faker->slug;
        $mockSqs = Mockery::mock(SqsClient::class)->makePartial();
        $firstReceivedMessage = ["data" => $this->faker->sentence];
        $repository = Mockery::mock(StorageRepository::class);
        $container = Mockery::mock(Container::class);
        $mockSqs->shouldReceive("receiveMessage")
            ->once()->with([
                "QueueUrl" => "/". $queueName,
                'AttributeNames' => ['ApproximateReceiveCount'],
            ])->andReturn(["Messages" => [$firstReceivedMessage, ["data" => $this->faker->sentence]]]);
        $job = Mockery::mock(SqsStorageJob::class);
        $mockFactory = Mockery::mock(JobFactory::class);
        $mockFactory->shouldReceive("create")->once()
                ->andReturnUsing(function ($pContainer, $pSqs, $pJob, $pConnection, $pQueue, $pRepository)
                    use($container, $firstReceivedMessage, $queueName, $repository, $job) {
                        $this->assertEquals($container, $pContainer);
                        $this->assertEquals($firstReceivedMessage, $pJob);
                        $this->assertEquals("/". $queueName, $pQueue);
                        $this->assertEquals($repository, $pRepository);
                        return $job;
                    }
                );
        $queue = new SqsStorageQueue(
            $mockSqs,
            $repository,
            $queueName,
            jobFactory: $mockFactory
        );
        $result = $queue->setContainer($container);
        $this->assertEquals($job, $queue->pop());
    }    
}