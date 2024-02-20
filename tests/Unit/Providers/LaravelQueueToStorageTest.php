<?php

use FelipeFrancesco\LaravelSQSLargePayload\Providers\LaravelQueueToStorageDriver;
use FelipeFrancesco\LaravelSQSLargePayload\Queue\Connectors\SqsStorageConnector;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;


final class LaravelQueueToStorageTest extends TestCase
{
    private $app_mock;

    private $service_provider;

    protected function setUp(): void
    {
        $this->app_mock = Mockery::mock(Application::class);

        $this->service_provider = new LaravelQueueToStorageDriver($this->app_mock);

        parent::setUp();
    }

    protected function tearDown() : void
    {
        Mockery::close();
    }    

    public function testItCanBeConstructed() {
        $this->assertInstanceOf(LaravelQueueToStorageDriver::class, $this->service_provider);
    }

    public function testItDoesAddsConnector() {
        $managerMock = Mockery::mock();
        $managerMock->shouldReceive("addConnector")->andReturnUsing(
            function ($name, $callback) {
                $this->assertEquals("sqs-large", $name);
                $this->assertInstanceOf(SqsStorageConnector::class, $callback());
            }
        )->once();
        $this->app_mock->shouldReceive("afterResolving")->andReturnUsing(
            function ($name, $callback) use($managerMock) {
                $this->assertEquals("queue", $name);
                $callback($managerMock);
            }
        );
        $this->service_provider->register();
    }

}