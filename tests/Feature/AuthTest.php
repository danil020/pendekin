<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registering and logging in issue revocable sanctum tokens', function () {
    $registration = $this->postJson('/api/register', [
        'name' => 'Pengguna Tes',
        'email' => 'pengguna@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $registration->assertCreated()
        ->assertJsonPath('data.email', 'pengguna@example.com');

    $registrationToken = $registration->json('token');
    expect($registrationToken)->toBeString()->not->toBeEmpty();

    $this->withToken($registrationToken)
        ->postJson('/api/logout')
        ->assertOk()
        ->assertJsonPath('message', 'Berhasil logout.');

    $login = $this->postJson('/api/login', [
        'email' => 'pengguna@example.com',
        'password' => 'password123',
    ]);

    $login->assertOk()->assertJsonPath('data.email', 'pengguna@example.com');

    $loginToken = $login->json('token');
    expect($loginToken)->toBeString()->not->toBeEmpty();

    $this->withToken($loginToken)
        ->postJson('/api/logout')
        ->assertOk();
});
