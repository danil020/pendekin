<?php

namespace App\Services;

use App\Models\Link;
use App\Models\LinkDailyStat;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LinkService
{
    private const LINK_CACHE_TTL_SECONDS = 3600;

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

    public function resolve(string $code): RedirectResponse|JsonResponse
    {
        $cacheKey = "link:{$code}";
        $linkData = Cache::remember($cacheKey, self::LINK_CACHE_TTL_SECONDS, function () use ($code): ?array {
            $link = Link::query()
                ->where('code', $code)
                ->first(['id', 'original_url', 'is_active', 'expires_at']);

            if ($link === null) {
                return null;
            }

            return [
                'id' => $link->id,
                'original_url' => $link->original_url,
                'is_active' => $link->is_active,
                'expires_at' => $link->expires_at?->toIso8601String(),
            ];
        });

        if ($linkData === null) {
            Cache::forget($cacheKey);

            return response()->json(['message' => 'Link tidak ditemukan.'], 404);
        }

        $this->applyExpiryAwareCacheTtl($cacheKey, $linkData);

        $expiresAt = $linkData['expires_at'] === null
            ? null
            : Carbon::parse($linkData['expires_at']);

        if (! $linkData['is_active'] || ($expiresAt !== null && $expiresAt->lt(now()))) {
            return response()->json(['message' => 'Link tidak aktif atau kedaluwarsa.'], 410);
        }

        $this->recordClick($linkData['id']);

        return redirect()->to($linkData['original_url'], 302);
    }

    /**
     * @return array{total_clicks: int, daily_stats: list<array{date: string, clicks: int}>}
     */
    public function dailyStats(Link $link, ?string $from = null, ?string $to = null): array
    {
        $dailyStats = $link->dailyStats()
            ->when($from !== null, fn (Builder $query) => $query->whereDate('date', '>=', $from))
            ->when($to !== null, fn (Builder $query) => $query->whereDate('date', '<=', $to))
            ->orderBy('date')
            ->get(['date', 'clicks']);

        return [
            'total_clicks' => (int) $dailyStats->sum('clicks'),
            'daily_stats' => $dailyStats->map(fn (LinkDailyStat $stat): array => [
                'date' => $stat->date->toDateString(),
                'clicks' => $stat->clicks,
            ])->all(),
        ];
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = Str::random(6);
        } while (Link::query()->where('code', $code)->exists());

        return $code;
    }

    private function recordClick(int $linkId): void
    {
        $now = now();

        LinkDailyStat::query()->upsert(
            [[
                'link_id' => $linkId,
                'date' => $now->toDateString(),
                'clicks' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['link_id', 'date'],
            [
                'clicks' => DB::raw('clicks + 1'),
                'updated_at' => DB::raw('CURRENT_TIMESTAMP'),
            ],
        );
    }

    /**
     * @param  array{id: int, original_url: string, is_active: bool, expires_at: string|null}  $linkData
     */
    private function applyExpiryAwareCacheTtl(string $cacheKey, array $linkData): void
    {
        if ($linkData['expires_at'] === null) {
            return;
        }

        $secondsRemaining = Carbon::parse($linkData['expires_at'])->getTimestamp() - now()->getTimestamp();

        if ($secondsRemaining <= 0) {
            Cache::forget($cacheKey);

            return;
        }

        if ($secondsRemaining < self::LINK_CACHE_TTL_SECONDS) {
            Cache::put($cacheKey, $linkData, $secondsRemaining);
        }
    }
}
