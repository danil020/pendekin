<?php

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Predis\Connection\ConnectionException as PredisConnectionException;

test('health reports a connected database', function () {
    $this->getJson('/health')
        ->assertOk()
        ->assertExactJson([
            'status' => 'ok',
            'database' => 'connected',
            'cache' => 'connected',
        ]);
});

test('health reports an unavailable database', function () {
    config(['session.driver' => 'database']);

    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getPdo')
        ->once()
        ->andThrow(new PDOException('Database unavailable.'));

    DB::shouldReceive('connection')->andReturn($connection);

    $this->getJson('/health')
        ->assertStatus(503)
        ->assertExactJson([
            'status' => 'error',
            'database' => 'disconnected',
            'cache' => 'connected',
        ]);
});

test('health remains successful when the cache is unavailable', function () {
    Cache::shouldReceive('get')
        ->once()
        ->andThrow(Mockery::mock(PredisConnectionException::class));

    $this->getJson('/health')
        ->assertOk()
        ->assertExactJson([
            'status' => 'ok',
            'database' => 'connected',
            'cache' => 'disconnected',
        ]);
});
