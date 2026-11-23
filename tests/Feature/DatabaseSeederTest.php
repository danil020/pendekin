<?php

use App\Models\Link;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('database seeder creates repeatable demo links and click stats', function () {
    $this->seed();
    $this->seed();

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('links', 4);
    $this->assertDatabaseCount('link_daily_stats', 21);
    $this->assertDatabaseHas('links', [
        'code' => 'portfolio',
        'is_alias' => true,
        'is_active' => true,
    ]);
    $this->assertDatabaseHas('links', [
        'code' => 'demo01',
        'is_alias' => false,
        'is_active' => true,
        'expires_at' => null,
    ]);

    $activeLink = Link::query()->where('code', 'demo01')->firstOrFail();

    $this->assertDatabaseHas('link_daily_stats', [
        'link_id' => $activeLink->id,
        'date' => today()->toDateString(),
        'clicks' => 9,
    ]);
});
