<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

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
            ['name' => 'Pengguna Demo', 'password' => 'password'],
        );

        $links = [
            [
                'code' => 'aktif1',
                'original_url' => 'https://example.com/aktif',
                'is_active' => true,
                'expires_at' => null,
            ],
            [
                'code' => 'nonaktif',
                'original_url' => 'https://example.com/nonaktif',
                'is_active' => false,
                'expires_at' => null,
            ],
            [
                'code' => 'kedaluwarsa',
                'original_url' => 'https://example.com/kedaluwarsa',
                'is_active' => true,
                'expires_at' => Carbon::now()->subDay(),
            ],
        ];

        foreach ($links as $link) {
            $user->links()->updateOrCreate(
                ['code' => $link['code']],
                [...$link, 'user_id' => $user->id, 'is_alias' => false],
            );
        }
    }
}
