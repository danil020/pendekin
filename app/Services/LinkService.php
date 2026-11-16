<?php

namespace App\Services;

use App\Models\Link;
use App\Models\LinkDailyStat;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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

    public function resolve(string $code): RedirectResponse|JsonResponse
    {
        $link = Link::query()->where('code', $code)->first();

        if ($link === null) {
            return response()->json(['message' => 'Link tidak ditemukan.'], 404);
        }

        if (! $link->is_active || ($link->expires_at !== null && $link->expires_at->lt(now()))) {
            return response()->json(['message' => 'Link tidak aktif atau kedaluwarsa.'], 410);
        }

        $this->recordClick($link);

        return redirect()->to($link->original_url, 302);
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

    private function recordClick(Link $link): void
    {
        $now = now();

        LinkDailyStat::query()->upsert(
            [[
                'link_id' => $link->id,
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
}
