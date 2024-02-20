<?php

use FelipeFrancesco\LaravelSQSLargePayload\Queue\Connectors\SqsStorageConnector;
use FelipeFrancesco\LaravelSQSLargePayload\Queue\SqsStorageQueue;
use PHPUnit\Framework\TestCase;

final class SqsStorageConnectorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testItCreatesQueue() {
        $faker = Faker\Factory::create();
        $queue = (new SqsStorageConnector())->connect([
            "queue" => $faker->slug,
            "region" => "sa-east-1"
        ]);
        $this->assertInstanceOf(SqsStorageQueue::class, $queue);
    }
}