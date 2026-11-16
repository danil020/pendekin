<?php

namespace App\Services;

use App\Models\Link;
use App\Models\User;
use Illuminate\Support\Str;

class LinkService
{
    /**
     * @param  array{original_url: string, alias?: string|null, expires_at?: string|null}  $data
     */
    public function create(User $user, array $data): Link
    {
        $alias = $data['alias'] ?? null;

        return $user->links()->create([
            'code' => $alias ?? $this->generateUniqueCode(),
            'original_url' => $data['original_url'],
            'is_alias' => $alias !== null,
            'is_active' => true,
            'expires_at' => $data['expires_at'] ?? null,
        ]);
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = Str::random(6);
        } while (Link::query()->where('code', $code)->exists());

        return $code;
    }
}
