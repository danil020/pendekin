<?php

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

test('health reports a connected database', function () {
    $this->getJson('/health')
        ->assertOk()
        ->assertExactJson([
            'status' => 'ok',
            'database' => 'connected',
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
        ]);
});
