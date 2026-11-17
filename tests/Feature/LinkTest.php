<?php

use App\Models\Link;
use App\Models\LinkDailyStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

test('an active link redirects and records its click', function () {
    $link = Link::factory()->create([
        'code' => 'go-there',
        'original_url' => 'https://example.com/destination',
    ]);

    $this->get('/go-there')
        ->assertStatus(302)
        ->assertHeader('Location', 'https://example.com/destination');

    expect(LinkDailyStat::query()->where('link_id', $link->id)->value('clicks'))->toBe(1);
});

test('a deactivated link returns gone', function () {
    Link::factory()->create([
        'code' => 'inactive',
        'is_active' => false,
    ]);

    $this->getJson('/inactive')
        ->assertGone();
});

test('an expired link returns gone', function () {
    Link::factory()->create([
        'code' => 'expired',
        'expires_at' => now()->subSecond(),
    ]);

    $this->getJson('/expired')
        ->assertGone();
});

test('an unknown code returns not found', function () {
    $this->getJson('/missing-code')
        ->assertNotFound();
});

test('two clicks increment the same daily stat atomically', function () {
    $user = User::factory()->create();
    $link = Link::factory()->for($user)->create(['code' => 'twice']);

    $this->get('/twice')->assertRedirect();
    $this->get('/twice')->assertRedirect();
    Sanctum::actingAs($user);

    $this->getJson("/api/links/{$link->id}/stats")
        ->assertOk()
        ->assertJsonPath('data.total_clicks', 2)
        ->assertJsonPath('data.daily_stats.0.date', today()->toDateString())
        ->assertJsonPath('data.daily_stats.0.clicks', 2);

    expect(LinkDailyStat::query()->where('link_id', $link->id)->count())->toBe(1);
});

test('a user cannot view another users link stats', function () {
    $link = Link::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/links/{$link->id}/stats")
        ->assertForbidden();
});

test('stats can be filtered by an inclusive date range', function () {
    $link = Link::factory()->create();
    LinkDailyStat::factory()->for($link)->create([
        'date' => today()->subDays(2),
        'clicks' => 4,
    ]);
    LinkDailyStat::factory()->for($link)->create([
        'date' => today()->subDay(),
        'clicks' => 3,
    ]);
    Sanctum::actingAs($link->user);

    $this->getJson("/api/links/{$link->id}/stats?from=".today()->subDay()->toDateString())
        ->assertOk()
        ->assertJsonPath('data.total_clicks', 3)
        ->assertJsonCount(1, 'data.daily_stats');
});

test('deactivation invalidates the cached redirect immediately', function () {
    $user = User::factory()->create();
    $link = Link::factory()->for($user)->create(['code' => 'cached-off']);
    $cacheKey = 'link:cached-off';

    $this->get('/cached-off')->assertRedirect();
    expect(Cache::has($cacheKey))->toBeTrue();

    Sanctum::actingAs($user);
    $this->patchJson("/api/links/{$link->id}/deactivate")->assertOk();

    expect(Cache::has($cacheKey))->toBeFalse();

    $this->getJson('/cached-off')->assertGone();
});

test('a cached link avoids querying the links table on subsequent redirects', function () {
    Link::factory()->create(['code' => 'cache-hit']);
    $this->get('/cache-hit')->assertRedirect();

    $connection = DB::connection();
    $connection->flushQueryLog();
    $connection->enableQueryLog();

    $this->get('/cache-hit')->assertRedirect();

    $linkQueries = collect($connection->getQueryLog())
        ->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'from "links"'));

    $connection->disableQueryLog();

    expect($linkQueries)->toBeEmpty();
});

test('changing a link invalidates its cached redirect', function () {
    $link = Link::factory()->create([
        'code' => 'cache-change',
        'original_url' => 'https://example.com/old',
    ]);

    $this->get('/cache-change')->assertRedirect('https://example.com/old');
    $link->update(['original_url' => 'https://example.com/new']);

    $this->get('/cache-change')->assertRedirect('https://example.com/new');
});

test('not found links are not cached', function () {
    $this->getJson('/not-cached')->assertNotFound();

    expect(Cache::has('link:not-cached'))->toBeFalse();
});
