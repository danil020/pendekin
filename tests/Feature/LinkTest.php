<?php

use App\Models\Link;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('a user can create a link with an alias', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/links', [
        'original_url' => 'https://example.com/landing',
        'alias' => 'my-link',
    ])
        ->assertCreated()
        ->assertJsonPath('data.code', 'my-link')
        ->assertJsonPath('data.short_url', rtrim((string) config('app.url'), '/').'/my-link')
        ->assertJsonPath('data.is_active', true);
});

test('a duplicate alias is rejected', function () {
    $user = User::factory()->create();
    Link::factory()->for($user)->create(['code' => 'claimed']);
    Sanctum::actingAs($user);

    $this->postJson('/api/links', [
        'original_url' => 'https://example.com/landing',
        'alias' => 'claimed',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('alias');
});

test('an alias with illegal characters is rejected', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/links', [
        'original_url' => 'https://example.com/landing',
        'alias' => 'bad_alias',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('alias');
});

test('an alias reserved for a route is rejected', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/links', [
        'original_url' => 'https://example.com/landing',
        'alias' => 'health',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('alias');
});

test('non-http urls are rejected', function (string $url) {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/links', ['original_url' => $url])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('original_url');
})->with([
    'javascript scheme' => 'javascript:alert(1)',
    'ftp scheme' => 'ftp://example.com/file',
]);

test('generated codes contain six alphanumeric characters and are unique', function () {
    Sanctum::actingAs(User::factory()->create());
    $codes = [];

    foreach (range(1, 2) as $index) {
        $response = $this->postJson('/api/links', [
            'original_url' => 'https://example.com/'.$index,
        ])->assertCreated();

        $codes[] = $response->json('data.code');
    }

    expect($codes[0])->toMatch('/^[A-Za-z0-9]{6}$/')
        ->and($codes[1])->toMatch('/^[A-Za-z0-9]{6}$/')
        ->and($codes[0])->not->toBe($codes[1]);
});

test('a user only sees their own links', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Link::factory()->for($user)->create(['code' => 'owned']);
    Link::factory()->for($otherUser)->create(['code' => 'private']);
    Sanctum::actingAs($user);

    $this->getJson('/api/links')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'owned');
});

test('a user cannot deactivate another users link', function () {
    $link = Link::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->patchJson("/api/links/{$link->id}/deactivate")
        ->assertForbidden();

    expect($link->fresh()->is_active)->toBeTrue();
});

test('a user can deactivate their own link', function () {
    $link = Link::factory()->create();
    Sanctum::actingAs($link->user);

    $this->patchJson("/api/links/{$link->id}/deactivate")
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    expect($link->fresh()->is_active)->toBeFalse();
});
