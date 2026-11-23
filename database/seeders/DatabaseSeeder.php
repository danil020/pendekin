<?php

namespace Database\Seeders;

use App\Models\LinkDailyStat;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            ['name' => 'Pengguna Demo', 'password' => Str::password(48)],
        );

        $links = [
            [
                'code' => 'demo01',
                'original_url' => 'https://example.com/demo',
                'is_alias' => false,
                'is_active' => true,
                'expires_at' => null,
            ],
            [
                'code' => 'portfolio',
                'original_url' => 'https://example.com/portfolio',
                'is_alias' => true,
                'is_active' => true,
                'expires_at' => null,
            ],
            [
                'code' => 'nonaktif',
                'original_url' => 'https://example.com/nonaktif',
                'is_alias' => false,
                'is_active' => false,
                'expires_at' => null,
            ],
            [
                'code' => 'kedaluwarsa',
                'original_url' => 'https://example.com/kedaluwarsa',
                'is_alias' => false,
                'is_active' => true,
                'expires_at' => Carbon::now()->subDay(),
            ],
        ];

        $seededLinks = [];

        foreach ($links as $link) {
            $seededLinks[$link['code']] = $user->links()->updateOrCreate(
                ['code' => $link['code']],
                [...$link, 'user_id' => $user->id],
            );
        }

        $sampleClicks = [
            'demo01' => [9, 7, 10, 5, 12, 8, 6],
            'portfolio' => [21, 18, 24, 16, 19, 14, 10],
            'nonaktif' => [4, 3, 2, 1],
            'kedaluwarsa' => [8, 6, 5],
        ];

        $sampleStats = [];
        $timestamp = now();

        foreach ($sampleClicks as $code => $dailyClicks) {
            foreach ($dailyClicks as $daysAgo => $clicks) {
                $sampleStats[] = [
                    'link_id' => $seededLinks[$code]->id,
                    'date' => today()->subDays($daysAgo)->toDateString(),
                    'clicks' => $clicks,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        LinkDailyStat::query()->upsert(
            $sampleStats,
            ['link_id', 'date'],
            ['clicks', 'updated_at'],
        );
    }
}
