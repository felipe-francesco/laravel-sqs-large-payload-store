<?php

use Aws\Sqs\SqsClient;
use FelipeFrancesco\LaravelSQSLargePayload\Queue\Jobs\SqsStorageJob;
use FelipeFrancesco\LaravelSQSLargePayload\Repository\StorageRepository;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

final class SqsStorageJobTest extends TestCase
{
    private $faker;

    protected function setUp(): void
    {
        $this->faker = Faker\Factory::create();
        parent::setUp();
    }

    protected function tearDown() : void
    {
        Mockery::close();
    }    

    public function testItReturnsRawBodyWhenIsNotSqsStorage() 
    {
        $body = json_encode(["data" => $this->faker->sentence()]);
        $payload = ["Body" => $body];
        $job = new SqsStorageJob(
            Container::getInstance(), 
            new SqsClient(["region" => "sa-east-1"]),
            $payload, 
            connectionName: $this->faker->slug, 
            queue: $this->faker->slug, 
            repository: Mockery::mock(StorageRepository::class)
        );
        $this->assertEquals($body, $job->getRawBody());
    }

    public function testItThrowsExceptionWithoutStorageConfiguration() 
    {
        $body = json_encode(["data" => $this->faker->sentence(), "isSqsStorage" => true]);
        $payload = ["Body" => $body];
        $repository = Mockery::mock(StorageRepository::class);
        $repository->shouldReceive("isValid")->once()->andReturn(false);
        $this->expectException(Exception::class);
        $job = new SqsStorageJob(
            Container::getInstance(), 
            new SqsClient(["region" => "sa-east-1"]),
            $payload, 
            connectionName: $this->faker->slug, 
            queue: $this->faker->slug, 
            repository: $repository
        );
        $job->getRawBody();
    }

    public function testItGetsPayloadFromStorage() 
    {
        $uuid = $this->faker->uuid;
        $body = json_encode([
            "data" => $this->faker->sentence(), 
            "isSqsStorage" => true,
            "uuid" => $uuid
        ]);
        $return = $this->faker->sentence();
        $payload = ["Body" => $body];

        $repository = Mockery::mock(StorageRepository::class);
        $repository->shouldReceive("isValid")->once()->andReturn(true);
        $repository->shouldReceive("getDataFromStorage")->once()->with($uuid)->andReturn($return);
        $repository->shouldReceive("removeFromStorage")->once()->with($uuid);
        
        $job = new SqsStorageJob(
            Container::getInstance(), 
            new SqsClient(["region" => "sa-east-1"]),
            $payload, 
            connectionName: $this->faker->slug, 
            queue: $this->faker->slug, 
            repository: $repository
        );
        $this->assertEquals($return, $job->getRawBody());
    }       

}