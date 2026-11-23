<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('registering and logging in issue revocable sanctum tokens', function () {
    $password = Str::password(12);

    $registration = $this->postJson('/api/register', [
        'name' => 'Pengguna Tes',
        'email' => 'pengguna@example.com',
        'password' => $password,
        'password_confirmation' => $password,
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
        'password' => $password,
    ]);

    $login->assertOk()->assertJsonPath('data.email', 'pengguna@example.com');

    $loginToken = $login->json('token');
    expect($loginToken)->toBeString()->not->toBeEmpty();

    $this->withToken($loginToken)
        ->postJson('/api/logout')
        ->assertOk();
});
